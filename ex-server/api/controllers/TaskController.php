<?php
/**
 * Created by PhpStorm.
 * User: op
 * Date: 2018-05-29
 * Time: 19:26
 */

namespace api\controllers;

use Yii;
use yii\db\Query;
use yii\db\Expression;
use yii\web\UploadedFile;
use yii\web\Session;
use yii\data\Pagination;
use api\models\Member;
use api\models\MemberVerified;
use api\models\Message;
use api\models\Transaction;
use api\models\EmailCode;
use api\models\Varcode;
use common\helpers\IdCardHelper;
use common\helpers\FileHelper;
use common\helpers\StringHelper;
use common\jinglan\Common;
use common\jinglan\Trade;
use common\jinglan\Jinglan;
use common\jinglan\CreateWallet;
use common\models\OtcMerchants;
use common\models\MemberWealthOrder;
use common\models\MemberWealthPackage;
use common\models\base\AccessToken;
use jinglan\bitcoin\Balance;
use Denpa\Bitcoin\Client as BitcoinClient;
use common\jinglan\DSActivity;
use jinglan\ves\VesRPC;
use common\jinglan\Reward;
use api\models\Coin;
use api\models\BalanceLog;

class TaskController extends ApibaseController
{
    public $modelClass = '';

    public function init(){
        parent::init();
    }



    /**
     * 锁仓收益返利定时任务
     */
    public function actionRelease()
    {
        //一个月之前的时间戳
        //var_dump(date('Y-m-d H:i:s',strtotime("-0 year -0 month -1 day")));die();
        //1.查询需要产生收益VIP订单
        $where = [
            'and',
            ['=', 'status', 1],
            ['>', 'surplus_period', 0],
            ['<', 'last_allocation', strtotime("-0 year -0 month -1 day")],
        ];
        $query = MemberWealthOrder::find();
        $data = $query->where($where)->select('*')->orderBy('ctime DESC')->asArray()->all();

        if (empty($data)){
            die('none order process ...');
        }
        
        $count = 0;
        foreach ($data as $k => &$x){
            $day_profit = $x['day_profit'];
            $order_amount = $x['amount'];
            $coin_symbol = $x['coin_symbol'];
            $uid = $x['uid'];
            $time_num = $x['last_allocation'] + 3600*24;
            //if ($time_num < 1567869316) {
                //$time_num = time();
            //}
            $time_num = time();
            $revenue = $order_amount * $day_profit / 100;

            $member_info = Member::find()->select('son_1_num,son_2_num')->where(['id'=>$uid])->asArray()->one();
            $bonus_rate = Reward::bonusRate($member_info);
            $bonus = $order_amount * $bonus_rate / 100;

            $sum_revenue = $revenue + $bonus;
            
            $type = $x['type'];

            if ($bonus > 0) {
                $release_log_str = $x['log'].'(时间'.date('Y-m-d H:i:s', $time_num).', 第'.(($x['period']-$x['surplus_period'])+1).'次释放, 普通收益:'.$revenue.', 加成收益:'.$bonus.', 剩余天数:'.($x['surplus_period']-1).')--';
            } else {
                $release_log_str = $x['log'].'(时间'.date('Y-m-d H:i:s', $time_num).', 第'.(($x['period']-$x['surplus_period'])+1).'次释放, 获得收益:'.$revenue.', 剩余天数:'.($x['surplus_period']-1).')--';
            }

            //更新订单状态
            $order_model = MemberWealthOrder::find()->where(['id'=>$x['id']])->one();
            $order_model->revenue = $order_model->revenue + $revenue;
            $order_model->get_profit = $order_model->get_profit + $sum_revenue;
            $order_model->surplus_period = $order_model->surplus_period -1;
            $order_model->last_allocation = $time_num;
            $order_model->log = $release_log_str;
            $order_model->save(false);

            // $coin_symbol2= Yii::$app->config->info('PLATFORM_COIN_SYMBOL');
            // $coin_info = Coin::find()->select("id,usd")->where(['symbol'=>$coin_symbol2,/*'ram_status'=>1*/])->one();
            // $usd_price2 = $coin_info['usd'];

            //加收益
            $type_str=['','','活期','定期','锁仓'];
            $tablePrefix = Yii::$app->db->tablePrefix;
            
            $memo = '矿机收益: '.$revenue.' '.$coin_symbol;
            if ($bonus > 0) {
               $memo = '矿机收益:'.$revenue.' '.$coin_symbol.'<br />邀请收益: '.$bonus.' '.$coin_symbol;
            }
            Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_balance", [ 
                'uid' => $uid,
                'uid2' => 0,
                'amount' => $sum_revenue,
                'coin_symbol' => $coin_symbol,
                'memo' => $memo,
                'ctime' => $time_num,
            ])->execute();
            $id = Yii::$app->db->getLastInsertID();

            // 读取balance_log数据表获取用户银行当前资产
            $balance = BalanceLog::find()
                ->select(['balance', 'network'])
                ->where(['member_id' => intval($uid)])
                ->andWhere(['coin_symbol' => $coin_symbol])
                ->orderBy('id DESC')
                ->one();
            if ($balance) {
                // 用户该地址在balance_log表中有记录
                $user_balance = $balance->balance;
                $network      = 0;
            }else{
                $user_balance = 0;
                $network      = 0;
            }
            // 数据库存储数据【balance_log表】
            $balance_log              = new BalanceLog();
            $balance_log->type        = 1;
            $balance_log->member_id   = intval($uid);
            $balance_log->coin_symbol = $coin_symbol;
            $balance_log->addr        = "";
            $balance_log->change      = $sum_revenue;
            $balance_log->balance     = (float)$user_balance +$sum_revenue;
            $balance_log->fee         = 0;
            $balance_log->detial      = $uid;
            $balance_log->detial_type = 'release';
            $balance_log->ctime       = time();
            $balance_log->network     = $network;
            $balance_log->memo     = $memo;

            $balance_log->save();

            //给上级返利
            $this->rebate($uid,$revenue,$coin_symbol,$time_num);
            $count += 1;
        }
        echo 'ok cout:'.$count;
        die();
    }

    public function actionFeeRelease(){
        //echo date("Y-m-d",strtotime("last Monday"));//上周一 
        $time1 = strtotime("last Monday");
        $time2 = $time1 + 3600*24*7;
        $status= intval(Yii::$app->config->info('FEE_FENHONG_STATUS'));
        $rate= intval(Yii::$app->config->info('FEE_FENHONG_RATE'));
        if($status == 1){
            $where = [
                'and',
                ['>=', 'ctime', $time1],
                ['<', 'ctime', $time2],
            ];              
            $data_order = (new \yii\db\Query())->from('jl_coins_order')->where($where)->asArray()->all();
            $fee_array = array();
            foreach ($data_order as $key => $value) {
                if($value['side']==1){//卖  手续费是 money
                    $coin = $value['money'];
                }else{
                    $coin = $value['stock'];
                }
                if(isset($fee_array[$coin])){
                    $fee_array[$coin] = $fee_array[$coin]+ $value['deal'];
                }else{
                    $fee_array[$coin] =  $value['deal'];
                }
            }
            //var_dump($fee_array);
            if(!empty($fee_array)){
            }
        }
    }

    public function actionFrozen(){


        $date_str = date('Y-m-d',time());
        $time_today = strtotime($date_str);
        $where = [
            'and',
            ['<', 'last_release_time', $time_today],
            ['>', 'freeze_rewards', 0],
        ];        
        $users_list = Member::find()->select('id,freeze_rewards')->where($where)->asArray()->all();
        $coin_symbol2= Yii::$app->config->info('PLATFORM_COIN_SYMBOL');
        foreach ($users_list as $key => $value) {
            Reward::dailyRelease($value['id'],$coin_symbol2);
        }
    }



    public function actionUpdatePrice(){

        $rpc = new VesRPC();
        $rpc_method = 'market.list';
        $rpc_params = [];        
        $rpc_ret = $rpc->do_rpc($rpc_method, $rpc_params);
        if($rpc_ret['code'] == 0){
             return 0;
        }else{
            $market_list = $rpc_ret['data'];
        }
        if (is_array($market_list)){
            $price_array = array();
            foreach ($market_list as $key => &$value) {
                $rpc_method2 = 'market.status_today';
                $rpc_params2 = [$value['name']];        
                $rpc_ret2 = $rpc->do_rpc($rpc_method2, $rpc_params2);
                if($rpc_ret2['code'] == 1){
                    $price_array[$value['name']] = $rpc_ret2['data']['last'];
                }            
            }

            $coins = Coin::find()->all();

            foreach ($coins as $coin) {
                $usd = 0;

                if(isset($price_array[strtoupper($coin->symbol)."USDT"])){
                    $coin->usd = $price_array[strtoupper($coin->symbol)."USDT"];
                }
                //遍历其他交易对
                foreach ($price_array as $key => $value) {
                    if(strlen($key)>4){
                        if(substr($key, strlen($key)-4) == "USDT"){
                            $coin2 = substr($key, 0, strlen($key)-4);
                            if(isset($price_array[strtoupper($coin->symbol).$coin2])){
                                $coin->usd = $price_array[strtoupper($coin->symbol).$coin2] *$value;
                            }
                        }
                    }
                }

                $coin->save();
            }

            //echo 'success';

        }

    }

    //给上级返利,参数为当前用户id,当前用户获得的收益
    private function rebate($uid,$money=0,$coin_symbol='HTC',$time_num){
        $user_info = Member::find()->where(['id'=>$uid])->asArray()->one();
        //上级节点奖励  -1-200001-200002-200003-200004-200005-
        $path = $user_info['path'];
        if (empty($path)) {
            return;
        }
        $path_arr =  explode("-",$path);
        $count = count($path_arr);
        for($i=0;$i<$count;$i++){
            $top_uid = intval($path_arr[$i]);         
            $level = $i + 1;
            $profit_num = $this->calc_top_profit($top_uid,$level,$money);
            echo "--->Top uid : $top_uid , level $level , num $profit_num \r\n";
            if($profit_num>0){
                //加返利
                $tablePrefix = Yii::$app->db->tablePrefix;
                Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_balance", [ 
                    'uid' => $top_uid,
                    'uid2' => $uid,
                    'amount' => $profit_num ,
                    'coin_symbol' => $coin_symbol,
                    'memo' => "用户{$uid}释放-层级$level-返奖励$profit_num",
                    'ctime' => $time_num,
                ])->execute();
                $id = Yii::$app->db->getLastInsertID();

                $parent_user = Member::find()->where(['id'=>$top_uid])->one();
 
                $parent_user['invite_rewards'] =  $parent_user['invite_rewards']+ $profit_num;
                $parent_user['freeze_rewards'] =  $parent_user['freeze_rewards']+ $profit_num;
                $parent_user['total_invite_rewards'] = $parent_user['total_invite_rewards']+ $profit_num;
        
                $parent_user->save();                         
            }
        }
    }

    //计算上级应得奖励
    private function calc_top_profit($top_uid,$level=1,$num){
        if ($level == 1) {
            return $num*0.1;
        }
        if ($level == 2) {
            return $num*0.06;
        }
        if ($level == 3) {
            return $num*0.03;
        }
        return 0;
    }


    public function actionDsactivity(){
        DSActivity::check_release();
        DSActivity::check_condition();
    } 


    public function actionPush(){
        $push_status = intval(Yii::$app->config->info("PUSH_QUOTATION_STATUS"));
        $rise_percent = floatval(Yii::$app->config->info("PUSH_COIN_RISE_PERCENT"));
        $fall_percent = floatval(Yii::$app->config->info("PUSH_COIN_FALL_PERCENT"));
        $fall_percent = 0 - $fall_percent;
        //echo $push_status." ";   
        //echo $rise_percent." ";     
        //echo $fall_percent." ";   

        $rpc = new VesRPC();
        $rpc_method = 'market.summary';
        $rpc_params = [];        
        $rpc_ret = $rpc->do_rpc($rpc_method, $rpc_params);
        if($rpc_ret['code'] == 0){
            $this->error_message('failed#1');
        }else{
            //var_dump($rpc_ret);die();
            $summary_data = $rpc_ret['data'];
        }
        if (is_array($summary_data)){
            foreach ($summary_data as $key => &$value) {
                $rpc_method2 = 'market.status_today';
                $rpc_params2 = [$value['name']];        
                $rpc_ret2 = $rpc->do_rpc($rpc_method2, $rpc_params2);
                if($rpc_ret2['code'] == 1){
                    $open = floatval($rpc_ret2['data']['open']);
                    $last = floatval($rpc_ret2['data']['last']);
                    $percent = 0;
                    if($open>0){
                        $percent = intval(($last - $open )*10000/$open)/100;
                    }
                    $arr = explode("USDT", $value['name']);
                    //var_dump($arr );
                    if(count($arr)>1){
                        $coin_symbol = $arr[0];
                        if($push_status > 0){
                            if($percent>0 &&  $percent>=$rise_percent){
                                $this->add_push($coin_symbol,$last,$percent);
                            }
                            if($percent<0 &&  $percent<=$fall_percent){  
                                $this->add_push($coin_symbol,$last,$percent);
                            }                         
                        }
                    }
                    
                    echo $value['name']."--".$open."--".$last."--".$percent."\r\n<br>";
                }            
            }
           $this->success_message("check ok");
        }else{
            $this->error_message('failed#2');
        }

    } 

    private function add_push($coin_symbol,$price_now,$percent){
        //先检测今天是否推送过
        $tablePrefix = Yii::$app->db->tablePrefix;
        $time_today = strtotime(date("Y-m-d",time()));
        $push_content = (new \yii\db\Query())
            ->from("{$tablePrefix}sys_push")
            ->where(['type'=>1,'extra'=>$coin_symbol]) 
            ->andWhere(['>=', 'add_time', $time_today])        
            ->one();
        //var_dump($push_content);
        if($push_content){
           return; 
        }
        $type = 1;
        $status = 0;
        if($percent>0){
            $title = $coin_symbol."上漲幅度超過".$percent."%";
            $content = $coin_symbol."當前價為".$price_now;
        }else{
            $title = $coin_symbol."下跌幅度超過".abs($percent)."%";
            $content = $coin_symbol."當前價為".$price_now;        
        }
        $status = $this->jpush($title,$content,$type,$coin_symbol);
        Yii::$app->db->createCommand()->insert("{$tablePrefix}sys_push", [ 
            'type' => $type ,
            'title' => $title,    
            'object' => $content,    
            'extra' => $coin_symbol,    
            'add_time' =>time(),     
            'status' => $status,                                              
           ])->execute();
            
        $id = Yii::$app->db->getLastInsertID();           
    }


    private function jpush($title,$content,$type,$extra){
        $app_key = \Yii::$app->config->info("JPUSH_APPKEY");
        $master_secret = \Yii::$app->config->info("JPUSH_SECRET");

        $title = $title;
        $content = $content;
        $type = $type;
        $url = $extra;

        $msg = array(
            'title' => $title,
            'extras' => array(
                'type' => $type,
                'content' => $content,
                'url' => $url,
            ),
        );

        $client = new \JPush\Client($app_key, $master_secret);

        $pusher = $client->push();
        $pusher->setPlatform('all');
        $pusher->options(array(
                    // apns_production: 表示APNs是否生产环境，
                    // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境
                    'apns_production' => false
                    ,//APP_DEBUG ? false : true,
                ));

        $pusher->addAllAudience();
        
        $pusher->androidNotification($content, $msg);

        $msg['alert'] = $title;
        unset($msg['title']);
        $pusher->iosNotification(['title'=>$title,'body'=>$content], $msg);
  
        try {
            $pusher->send();
            return 1;

        } catch (\JPush\Exceptions\JPushException $e) {
            // try something else here
            //print $e;
            return -1;

        }        
    }

}