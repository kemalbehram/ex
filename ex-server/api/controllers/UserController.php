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
use common\models\MemberActivity;

use common\models\base\AccessToken;
use jinglan\bitcoin\Balance;
use Denpa\Bitcoin\Client as BitcoinClient;
use common\jinglan\Reward;
use api\models\Coin;
use api\models\BalanceLog;

class UserController extends ApibaseController
{
    public $modelClass = '';

    public function init(){
        parent::init();
    }

    public function actionUserInfo(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $member = MemberVerified::findOne(['uid'=>$uinfo['id']]);
        if(empty($member)){
            $member = ['real_name'=>'','status'=>0];
        }
        $language = $request->post('language');
        $language =  $language == 'en_us' ? 'en_us' : 'zh_cn';
        $status_msg = [
            Yii::t($language,'_No_Certification_Certified_In_Time_'),
            Yii::t($language,'_Waiting_For_Audit_'),
            Yii::t($language,'_Audit_Has_Passed_'),
            Yii::t($language,'_Audit_Failed_Upload_Real_Info_'),
        ];
        $data = array(
            'UID'               => $uinfo['id'],
            'username'              => $uinfo['username'],
            'name'              => $member['real_name'],
            // 'verified_status'   => $uinfo['verified_status'],
            'verified_level'   => $uinfo['verified_level'],
            'is_google_check'   => $uinfo['is_google_check'],
            'two_step_open_status'   => $uinfo['two_step_open_status'],
            'bank_status' => $uinfo['bank_status'],
            'info_status' => $uinfo['info_status'],
            'status'            => $member['status'],
            'mobile_phone'      => $uinfo['mobile_phone'],
            'email'             => $uinfo['email'],
            'nickname'          => $uinfo['nickname'],
            // 'otc_merchant'      => $uinfo['otc_merchant'],
            // 'status_msg'        => $status_msg[$member['status']],
            'usd_to_cny'        => Jinglan::usd_to_cny(),
        );
        /********************实名认证及商户认证状态-开始********************/
        // 实名认证
        $member_verified = MemberVerified::find()->select(['status'])->where(['uid'=>intval($uinfo['id'])])->andWhere(['<>','status',0])->asArray()->one();
        if ($member_verified) {
            $data['verified_status'] = $member_verified['status'];
            $data['verified_status_msg'] = $status_msg[$member_verified['status']];
        }else{
            $data['verified_status'] = "0";
            $data['verified_status_msg'] = $status_msg[0];
        }
        // 商户认证
        $otc_merchants = OtcMerchants::find()->select(['status'])->where(['uid'=>intval($uinfo['id'])])->andWhere(['<>','status',0])->asArray()->one();
        if ($otc_merchants) {
            $data['otc_merchant'] = $otc_merchants['status'];
            $data['otc_merchant_msg'] = $status_msg[$otc_merchants['status']];
        }else{
            $data['otc_merchant'] = "0";
            $data['otc_merchant_msg'] = $status_msg[0];
        }
        /********************实名认证及商户认证状态-结束********************/
        $this->success_message($data);
    }

    public function actionGetInfo(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $uid = $uinfo['id'];
        $data = MemberVerified::find()->where(['uid' => $uid])->andWhere(['>', 'status', '0'])->asArray()->one();
        if(empty($data)){
            $this->error_message('_No_Certification_Certified_In_Time_');
        }
        $request = Yii::$app->request;
        $language = $request->post('language');
        $language =  $language == 'en_us' ? 'en_us' : 'zh_cn';
        $data['id_card_img'] = $this->get_user_avatar_url($data['id_card_img']);
        $data['id_card_img2'] = $this->get_user_avatar_url($data['id_card_img2']);
        $status_msg = [
                        Yii::t($language,'_Deleted_'),
                        Yii::t($language,'_Waiting_For_Audit_'),
                        Yii::t($language,'_Audit_Has_Passed_'),
                        Yii::t($language,'_Audit_Failed_Upload_Real_Info_'),
                    ];
        $data['status_msg'] = $status_msg[$data['status']];
        $this->success_message($data);
    }

    //邀请信息
    public function actionInviteInfo(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $data['coin_symbol'] = Yii::$app->config->info('PLATFORM_COIN_SYMBOL');
        $data['invite_url'] =  Yii::$app->request->hostInfo.'/#/register?code='.$uinfo['code'];
        $data['invite_code'] = $uinfo['code'];
        $data['level_1_num'] = $uinfo['son_1_num'];
        $data['level_2_num'] = $uinfo['son_2_num'];
        $data['level_3_num'] = $uinfo['son_3_num'];
        $data['total_invite_rewards'] = sprintf('%.2f',$uinfo['total_invite_rewards']);
        $data['invite_rewards'] = sprintf('%.2f',$uinfo['invite_rewards']);
        $data['fee_rewards'] = sprintf('%.2f',$uinfo['invite_fee_rewards']);
        $data['freeze_rewards'] = sprintf('%.2f',$uinfo['freeze_rewards']);
        $data['frozen_rewards'] = sprintf('%.2f',$uinfo['frozen_rewards']);
        $this->success_message($data);
    }

    //邀请排行
    public function actionInviteRank(){
        $member_info = Member::find()->select('id,username,total_invite_rewards')->orderBy('total_invite_rewards DESC')->limit(5)->asArray()->all();;

        foreach ($member_info as &$value) {
            $value['username'] = $this->hideStar($value['username']);
            $value['total_invite_rewards'] = sprintf('%.2f',$value['total_invite_rewards']);
        }
        $this->success_message($member_info);
    }



    //DS活动-我的锁仓列表
    public function actionMyweath2(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $member_info = MemberActivity::find()->where(['uid'=>$uinfo['id']])->select('*')->orderBy('id DESC')->limit(50)->asArray()->all();

        $this->success_message($member_info);
    }

    //DS活动-我的收益记录
    public function actionWeathprofit2(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $uid = $uinfo['id'];

        $rows = (new \yii\db\Query())
            ->select(['coin_symbol','amount','log_time','memo'])
            ->from('jl_member_activity_log')
            ->where(['uid' => $uid])
            ->all();

        foreach ($rows as &$value) {
            $value['log_time'] = date('Y-m-d H:i:s',$value['log_time']);;
        }

        $this->success_message($rows);
    }

    //我的矿机
    public function actionMyweath(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $member_info = MemberWealthOrder::find()->where(['uid'=>$uinfo['id']])->select('id,amount,icon,type,coin_symbol,name,wealth_pid,period,surplus_period,ctime,end_time,status')->orderBy('ctime DESC')->limit(50)->asArray()->all();
        $type=['','','活期','定期','锁仓'];
        foreach ($member_info as &$value) {
            $value['type_desc'] = $type[$value['type']];
        }
        $this->success_message($member_info);
    }

    //系统矿机数据
    public function actionOverview(){
        $data['coin_symbol'] = Yii::$app->config->info('PLATFORM_COIN_SYMBOL');

        $rows = (new \yii\db\Query())
            ->select(['id','amount'])
            ->from('jl_member_wealth_balance')
            ->where(['coin_symbol' => $data['coin_symbol']])
            ->andWhere(['>', 'amount', '0'])
            ->all();
        $totalCount = 680000000;
        $produced_count = 0;
        foreach ($rows as $key => $value) {
            $produced_count = $produced_count + $value['amount'];
        }
        $left_count = $totalCount - $produced_count;

        $return = array();
        $return['coin_symbol'] = $data['coin_symbol'];
        $return['totalCount'] = $totalCount;
        $return['produced_count'] = $produced_count;
        $return['left_count'] = $left_count;

        $this->success_message($return);
    }

    //矿机列表
    public function actionWeathpackage(){
        $request = Yii::$app->request;
        $member_info = MemberWealthPackage::find()->where(['status'=>1, 'type'=>3])->select('id,type,icon,coin_symbol,name,period,min_num,max_num, day_profit')->orderBy('ctime DESC')->limit(50)->asArray()->all();
        
        $type=['','','活期','定期',''];
        foreach ($member_info as &$value) {
            $value['type'] = $type[$value['type']];
        }
        $this->success_message($member_info);
    }

    //锁仓套餐
    public function actionWeathpackagelock(){
        $request = Yii::$app->request;
        $member_info = MemberWealthPackage::find()->where(['status'=>1,'type'=>4])->select('id,type,icon,coin_symbol,name,period,min_num,max_num, day_profit')->orderBy('ctime DESC')->limit(50)->asArray()->all();
        
        $type=['','','','','锁仓'];
        foreach ($member_info as &$value) {
            $value['type'] = $type[$value['type']];
        }
        $this->success_message($member_info);
    }

    //套餐详情
    public function actionWeathpackageinfo(){
        $request = Yii::$app->request;
        $id = intval($request->post('id'));
        if (!($id > 0)) {
        	$this->error_message('_Unlawful_Request_');
        }
        $info = MemberWealthPackage::find()->where(['id'=>$id ,'status'=>1])->select('id,type,icon,coin_symbol,name,period,min_num,max_num, day_profit')->asArray()->one();
        
        $type=['','','','定期','锁仓'];
        $info['type'] = $type[$info['type']];
        $this->success_message($info);
    }

    //收益记录
    public function actionWeathprofit(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $uid = $uinfo['id'];

        $rows = (new \yii\db\Query())
            ->select(['ctime','memo'])
            ->from('jl_member_wealth_balance')
            ->where(['uid' => $uid])
            ->orderBy('ctime DESC')
            ->all();

        foreach ($rows as &$value) {
            $value['ctime'] = date('Y-m-d H:i:s',$value['ctime']);;
        }

        $this->success_message($rows);
    }

    //购买矿机
    public function actionWeathbuypackage(){
        $request  = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $uid = $uinfo['id'];
        $id       = $request->post('id');
        $num       = floatval($request->post('num'));
        $coin_symbol       = $request->post('coin_symbol');

        if(empty($id)){
          $this->error_message('该商品已经售完');
        }
        if(empty($num)){
          $this->error_message('请输入购买金额');
        }
        if(empty($coin_symbol)){
          $this->error_message('请输入购买币种');
        }
        $package_info = MemberWealthPackage::find()->where(['status'=>1,'id'=>$id])->select('id,type,icon,coin_symbol,name,period,min_num,max_num,day_profit')->orderBy('ctime DESC')->limit(50)->asArray()->one();
        if(empty($package_info)){
          $this->error_message('该商品已经售完');
        }
        if($num < $package_info['min_num']){
          $this->error_message('购买金额过低,最低'.sprintf('%.2f',$package_info['min_num']));
        }
        if($num > $package_info['max_num']){
            $this->error_message('购买金额过高,最高'.sprintf('%.2f',$package_info['max_num']));
        }         
        //$coin_symbol = $package_info['coin_symbol'];

        //最多购买8个

        $where = [
            'and',
            ['in', 'type', [3, 4]],
            ['=', 'uid', $uid],
        ];
        $order_count = MemberWealthOrder::find()->select("id")->orderBy('id desc')->where($where)->asArray()->count();
        $order_count = intval($order_count);
        if($order_count>=8){
            $this->error_message('已经达到最大购买次数！');
        }

        // $usd_price = Reward::coin_usd_price($coin_symbol);

        // 获取用户资产
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($uid);// 成功返回数据，失败返回false
        if (!$balance_all) {
            $this->error_message('_The_application_process_failed_unexpectedly_Please_try_again_later_');
        }
        foreach ($balance_all[0] as $key => $value) {
            if ($value['name'] == $coin_symbol) {
                if ($value['available'] < $num) {
                    $this->error_message("可用 $coin_symbol 余额不足");
                }
                break;
            }
        }
        if($package_info['type'] == 4){
            $memo = "锁仓花费 ";
        }else{
            // $memo = "购买矿机花费 ";
            $memo = "定期花费 ";
        }

        //扣费
        $tablePrefix = Yii::$app->db->tablePrefix;
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_balance", [ 
            'uid' => $uid,
            'uid2' => 0,
            'amount' => - $num,
            'coin_symbol' => $coin_symbol,
            'memo' => $memo.$num.' '.$coin_symbol,
            'ctime' => time(),
        ])->execute();
        $id = Yii::$app->db->getLastInsertID();
        if (!$id) {
            $this->error_message("系统繁忙请稍后重试");
        }

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
        $balance_log->type        = 10;
        $balance_log->member_id   = intval($uid);
        $balance_log->coin_symbol = $coin_symbol;
        $balance_log->addr        = "";
        $balance_log->change      = - $num;
        $balance_log->balance     = (float)$user_balance -$num;
        $balance_log->fee         = 0;
        $balance_log->detial      = $uid;
        $balance_log->detial_type = 'wealth';
        $balance_log->ctime       = time();
        $balance_log->network     = $network;
        $balance_log->memo     = $memo. ', 数量: '.$num.' '.$coin_symbol;

        $balance_log->save();

        //活期且已经买过直接加订单数量，写log,返回。定期直接加记录
        if ($package_info['type'] == 2) {
            $where = [
                'and',
                ['=', 'status', 1],
                ['=', 'type', 2],
                ['=', 'uid', $uid],
            ];
            $order_model = MemberWealthOrder::find()->orderBy('id desc')->where($where)->one();
            if (!empty($order_model)){
                $release_log_str = $order_model->log.'(时间'.date('Y-m-d H:i:s', time()).',追加购买:'.$num.$coin_symbol.',购买后余额'.($order_model->amount + $num).')--';
                $order_model->amount = $order_model->amount + $num;
                $order_model->log = $release_log_str;
                $order_model->save(false);
                $this->success_message('购买成功');
                die();
            }
        }


        //写记录
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_order", [
            'uid' => $uid,
            'icon' => $package_info['icon'],
            'type' => $package_info['type'],
            'order_id' => $id,
            'wealth_pid' => $package_info['id'],
            'name' => $package_info['name'],
            'period' => $package_info['period'],
            'day_profit' => $package_info['day_profit'],
            'surplus_period' => $package_info['period'],
            'status' => 1,
            'amount' => $num ,
            'coin_symbol' =>  $package_info['coin_symbol'],
            'ctime' => time(),
            'last_allocation' => time(),
        ])->execute();
        $id = Yii::$app->db->getLastInsertID();


        $coin_symbol2= Yii::$app->config->info('PLATFORM_COIN_SYMBOL');
        $usd_price2 = Reward::coin_usd_price($coin_symbol2);
        if($usd_price2<=0){
            $coin_info = Coin::find()->select("id,usd")->where(['symbol'=>$coin_symbol2,/*'ram_status'=>1*/])->one();
            $usd_price2 = $coin_info['usd'];
        }
        //给上级返利
        $this->rebate($uid,$num/$usd_price2,$coin_symbol2,time());


        $this->success_message('购买成功');
    }
    
    /*
     * 锁仓赎回
     * 定期产品不能赎回，到期自动返回wallet
     */
    public function actionWeathredeem(){
        $request  = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $uid = intval($uinfo['id']);
        $id       = $request->post('id');

        if(!($id > 0)){
          $this->error_message('请求参数错误');
        }
        
        $order_model = $order_info = MemberWealthOrder::findOne($id);
        if ($order_info['uid'] !== $uid) {
            $this->error_message('非本人操作');
        }
        if ($order_info['status'] !== 1) {
            $this->error_message('该订单无效');
        }
        if ($order_info['type'] !== 4) {
            $this->error_message('非锁仓订单无法赎回');
        }
        
        $coin_symbol = $order_info['coin_symbol'];
        $num = $order_info['amount'];
        $memo = "锁仓赎回 ";

        //锁仓赎回
        $tablePrefix = Yii::$app->db->tablePrefix;
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_balance", [ 
            'uid' => $uid,
            'uid2' => 0,
            'amount' => $num,
            'coin_symbol' => $coin_symbol,
            'memo' => $memo.$num.' '.$coin_symbol,
            'ctime' => time(),
        ])->execute();
        $id = Yii::$app->db->getLastInsertID();
        if (!$id) {
            $this->error_message("系统繁忙请稍后重试");
        }

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
        $balance_log->change      = $num;
        $balance_log->balance     = (float)$user_balance + $num;
        $balance_log->fee         = 0;
        $balance_log->detial      = $uid;
        $balance_log->detial_type = 'wealth_redeem';
        $balance_log->ctime       = time();
        $balance_log->network     = $network;
        $balance_log->memo     = $memo.',数量: '.$num.' '.$coin_symbol;

        $balance_log->save();
        
        $order_model->status = 2;
        $release_log_str = $order_model->log.'; (已于'.date('Y-m-d H:i:s', time()).'赎回:'.$num.$coin_symbol;
        $order_model->end_time = time(); // 赎回时间
        $order_model->log = $release_log_str;
        $order_model->save();
        $this->success_message('赎回成功');
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
            //echo "--->Top uid : $top_uid , level $level , num $profit_num \r\n";
            if($profit_num>0){
                //加返利
                $tablePrefix = Yii::$app->db->tablePrefix;
                Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_balance", [ 
                    'uid' => $top_uid,
                    'uid2' => $uid,
                    'amount' => $profit_num ,
                    'coin_symbol' => $coin_symbol,
                    'memo' => "用户{$uid}购买-层级$level-返奖励$profit_num",
                    'ctime' => time(),
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
            return $num*0.03;
        }
        if ($level == 2) {
            return $num*0.02;
        }
        if ($level == 3) {
            return $num*0.01;
        }
        return 0;
    }

    //获取api key
    public function actionApiKey(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $data['access_token'] = $uinfo['access_token']; 
        $this->success_message($data);
    }
    //用户名、邮箱、手机账号中间字符串以*隐藏 
    private function hideStar($str) { 
      if (strpos($str, '@')) {
        $email_array = explode("@", $str); 
        $prevfix = (strlen($email_array[0]) < 4) ? "" : substr($str, 0, 3); //邮箱前缀 
        $count = 0; 
        $str = preg_replace('/([\d\w+_-]{0,100})@/', '***@', $str, -1, $count); 
        $rs = $prevfix . $str; 
      } else { 
        $pattern = '/(1[3458]{1}[0-9])[0-9]{4}([0-9]{4})/i'; 
        if (preg_match($pattern, $str)) { 
          $rs = preg_replace($pattern, '$1****$2', $str); // substr_replace($name,'****',3,4); 
        } else { 
          $rs = substr($str, 0, 3) . "***" . substr($str, -1); 
        } 
      } 
      return $rs; 
    } 

    // 实名认证
    public function actionSetReal(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $uid = $uinfo['id'];
        $this->check_submit($uid);
        $real_name = $request->post('real_name');
        $id_number = $request->post('id_number');
        $id_card_img = $request->post('id_card_img');
        $id_card_img2 = $request->post('id_card_img2');
        $this->check_empty($real_name, '_Name_NOT_Empty_');
        $this->check_empty($id_number, '_IdCard_Not_Empty_');
        $this->check_empty($id_card_img, '_Upload_IdCard_Front_');
        $this->check_empty($id_card_img2, '_Upload_IdCard_Back_');
        $this->check_img($id_card_img);
        $this->check_img($id_card_img2);
        $IDCard = new IdCardHelper();
        if(!$IDCard->validation_filter_id_card($id_number)){
            $this->error_message('_IdCard_Format_Wrong_');
        }
        if($this->save_info($uid,$real_name,$id_number,$id_card_img,$id_card_img2)){
            $this->success_message();
        }else{
            $this->error_message('_Save_Failure_Try_Again_');
        }
    }

    /**
     * 检查是否能提交
     */
    private function check_submit($uid){
        $exist = MemberVerified::find()->where(['uid' => $uid])->asArray()->one();
        if(empty($exist)){
            return true;
        }else{
            if(in_array($exist['status'],[1,2])){
                $this->error_message('_Have_Submit_Auth_Info_');
            }else{
                return true;
            }
        }
    }

    /**
     * 检查图片是否存在
     */
    private function check_img($url){
        $file = Yii::getAlias("@rootPath/web") . $url;
        if(!file_exists($file)){
            $this->error_message('_Picture_Not_Exist_Reupload_');
        }
    }

    /**
     * @param $uid
     * @param $real_name
     * @param $id_number
     * @param $url1
     * @param $url2
     * @return bool
     */
    private function save_info($uid,$real_name,$id_number,$url1,$url2){
        $model = MemberVerified::findOne(['uid' => $uid]);
        if(empty($model)) {
            $model = new MemberVerified();
            $model->uid = $uid;
        }
        $model->real_name = $real_name;
        $model->id_number = $id_number;
        $model->id_card_img  = $url1;
        $model->id_card_img2 = $url2;
        $model->status = 1;
        $model->ctime = date('Y-m-d H:i:s');
        return $model->save();
    }

    /**
     * 上传图片
     */
    public function actionUploadImage(){
        // $uid = Yii::$app->user->identity->user_id;
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $uid = $uinfo['id'];
        $this->check_submit($uid);
        $file = $_FILES['image'];
        $data = $this->upload($file, 'image');
        $this->success_message($data);
    }

    /**
     * @param $file
     * @param $name
     * @return array
     */
    private function upload($file,$name){
        $type = 'imagesUpload';
        $uploadConfig = Yii::$app->params[$type];
        $stateMap = Yii::$app->params['uploadState'];
        $file_size = $file['size'];
        $file_name = $file['name'];
        $file_exc = StringHelper::clipping($file_name);
        if ($file_size > $uploadConfig['maxSize']){
            $message = $stateMap['ERROR_SIZE_EXCEED'];
            $this->error_message($message);
        } else if (!$this->checkType($file_exc, $type)){
            $message = $stateMap['ERROR_TYPE_NOT_ALLOWED'];
            $this->error_message($message);
        } else {
            if (!($path = $this->getPath($type))) {
                $message = '_Folder_Creation_Failed__IsOpen_Attachment_Write_Permission_';
                $this->error_message($message);
            }
            $filePath = $path . $uploadConfig['prefix'] . StringHelper::random(10) . $file_exc;
            $uploadFile = UploadedFile::getInstanceByName($name);
            if ($uploadFile->saveAs(Yii::getAlias("@attachment/") . $filePath)) {
                $data = [
                    'urlPath' => Yii::getAlias("@attachurl/") . $filePath,
                ];
                return $data;
            } else {
                $message = '_File_Move_Error_';
                $this->error_message($message);
            }
        }
    }
      /**
     * 返回带协议的域名
     */
    protected function actionGetHost(){
        $host=$_SERVER["HTTP_HOST"];
        $protocol=$this->is_ssl()?"https://":"http://";
        return $protocol.$host;
    }
    /**
     * 判断是否SSL协议
     * @return boolean
     */
    function is_ssl() {
        if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
            return true;
        }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }
    /**
     * 文件类型检测
     *
     * @param $ext
     * @param $type
     * @return bool
     */
    private function checkType($ext, $type)
    {
        if(empty(Yii::$app->params[$type]['maxExc']))
        {
            return true;
        }

        return in_array($ext, Yii::$app->params[$type]['maxExc']);
    }
    
    /**
     * 获取文件路径
     *
     * @param $type
     * @return string
     */
    public function getPath($type)
    {
        // 文件路径
        $file_path = Yii::$app->params[$type]['path'];
        // 子路径
        $sub_name = Yii::$app->params[$type]['subName'];
        $path = $file_path . date($sub_name,time()) . "/";
        $add_path = Yii::getAlias("@attachment/") . $path;
        // 创建路径
        FileHelper::mkdirs($add_path);
        return $path;
    }

    public function actionPasswordEdit(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $uinfo = $os == 'web' ? $this->memberToken($access_token) : $this->checkToken($access_token);
        $oldpassword = $request->post('oldpassword');
        $this->check_empty($oldpassword,'_The_Original_Password_Can_Not_Be_Empty_');

        $password = $request->post('password');
        $this->check_empty($password,'_Password_Cannot_Be_empty_');
        if(strlen($password)<6){
            $this->error_message('_Enter_at_least_6_digits_of_the_password');
        }
        $repassword = $request->post('repassword');
        $this->check_empty($repassword,'_Confirm_Password_Must_Not_Be_Empty_');
        if($password  !== $repassword){
            $this->error_message('_The_Two_Password_Input_Is_Inconsistent_');
        }
        $member_model = Member::find()->where(['id'=>$uinfo['id']])->one();
        $ha =  $member_model['password_hash'];
        $model = Yii::$app->getSecurity()->validatePassword($oldpassword, $ha);
        if($model == 1){
            $password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $member_model->password_hash = $password_hash;
            $member_model->updated_at = time();
            $member_model->save(false);
            $this->success_message();
        }else{
            $this->error_message('_The_Original_Password_Is_Incorrect_');
        }
    }

    public function actionNicknameEdit(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $uinfo = $os == 'web' ? $this->memberToken($access_token) : $this->checkToken($access_token);
        $nickname = $request->post('nickname');
        $member = Member::findOne(['id'=>$uinfo['id']]);
        $member->nickname = $nickname;
        if($member->save()>0){
            $this->update_user_level($uinfo['id']);
            $this->success_message('修改成功!');
        }
    }


    public function actionInfoEdit(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $uinfo = $os == 'web' ? $this->memberToken($access_token) : $this->checkToken($access_token);
        $country = $request->post('country');
        $this->check_empty($country,'国家和地区不能为空');
        $credential_type = $request->post('credential_type');
        $this->check_empty($credential_type,'证件类型不能为空');
        $credential_no = $request->post('credential_no');
        $this->check_empty($credential_no,'证件号码不能为空'); 
        $first_name = $request->post('first_name');
        $this->check_empty($first_name,'名字不能为空');   
        $last_name = $request->post('last_name');
        $this->check_empty($last_name,'姓氏不能为空');
        $birthday = $request->post('birthday');
        $this->check_empty($birthday,'生日不能为空');
        $contact_address = $request->post('contact_address');
        $this->check_empty($contact_address,'联络地址不能为空');  
        $fund_use = $request->post('fund_use');
        $this->check_empty($fund_use,'资金用途不能为空');   

        $uid = $uinfo['id'];

        $tablePrefix = Yii::$app->db->tablePrefix;

        $info = (new \yii\db\Query())
            ->select('*')
            ->from("{$tablePrefix}member_info ")
            ->where(['uid'=>$uid])
            ->one();
        
        if(!empty($info)){
            $update = Yii::$app->db->createCommand()->update("{$tablePrefix}member_info",
                array(
                    'country' => $country,
                    'credential_type' => $credential_type,    
                    'credential_no' => $credential_no,   
                    'first_name' => $first_name,       
                    'last_name' => $last_name,      
                    'birthday' => $birthday,
                    'contact_address' => $contact_address,  
                    'fund_use' => $fund_use,  
                    'status' => 1,    
                ),
                "uid=".$uid
            )->execute();
            $member = Member::findOne(['id'=>$uid]);
            $member->info_status = 1;
            if($member->save()>0){
                $this->update_user_level($uid);
                $this->success_message('','資料修改成功!');
            }               
            $this->update_user_level($uid);
            $this->success_message('','資料修改成功!');
        }else{
            Yii::$app->db->createCommand()->insert("{$tablePrefix}member_info", [ 
                'uid' => $uid,   
                'country' => $country,
                'credential_type' => $credential_type,    
                'credential_no' => $credential_no,   
                'first_name' => $first_name,       
                'last_name' => $last_name,      
                'birthday' => $birthday,
                'contact_address' => $contact_address,  
                'fund_use' => $fund_use,  
                'status' => 1,                                              
               ])->execute();
                
            $id = Yii::$app->db->getLastInsertID(); 
            if($id>0){
                $member = Member::findOne(['id'=>$uid]);
                $member->info_status = 1;
                if($member->save()>0){
                    $this->update_user_level($uid);
                    $this->success_message('','資料修改成功!');
                }                
            }else{
                $this->error_message('資料修改失敗!');
            }  
        }



    }


     public function actionBindEmail(){
        $request = Yii::$app->request;
        $email = $request->post('email');
        $varcode = $request->post('varcode');
        $password = $request->post('password');
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $uinfo = $os == 'web' ? $this->memberToken($access_token) : $this->checkToken($access_token);         
        $this->check_empty($email,'邮箱不能为空');
        $this->check_empty($password,'账户密码不能为空');
        if(!preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.[a-zA-Z0-9]{2,6}$/",$email)){
             $this->error_message('邮箱地址不合法');
         }
        
        $member_model = Member::find()->where(['id'=>$uinfo['id']])->one();

        $model = Yii::$app->getSecurity()->validatePassword($password, $member_model['password_hash'] );
        if($model != 1){
            $this->error_message('_The_Password_Error_');
        }
        $varcode_result = EmailCode::find()->where( ['email'=>$email, 'varcode'=>$varcode,'type'=>3] )->one();
        if (empty($varcode_result)){
             $this->error_message('邮箱验证码错误');

        }
         $var = Member::find()->where(['email'=>$email])->one();
         if(!empty($var)){
             $this->error_message('该邮箱已经注册,请勿重复注册');
         }
         $ret = Member::find()->where(['id'=>$uinfo['id']])->asArray()->one();
         //var_dump($ret);
         if(!empty($ret['email'])){

             $this->error_message('你已经绑定过邮箱了');
         }  

        $member = Member::findOne(['id'=>$uinfo['id']]);
        $member->email =$email;

        if($member->save()>0){
            $session = new Session;
            $session->open();
            $session["email"]= $email;     
            $this->update_user_level($uinfo['id']);
            $this->success_message('绑定邮箱成功!');
        }                
     }

     public function actionBindPhone(){
        $request = Yii::$app->request;
        $phone = $request->post('phone');
        $varcode = $request->post('varcode');
        $password = $request->post('password');
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $uinfo = $os == 'web' ? $this->memberToken($access_token) : $this->checkToken($access_token);         
        $this->check_empty($phone,'手机号不能为空');
        $this->check_empty($password,'账户密码不能为空');

        $phone = Jinglan::check_mobile_phone($phone);

        $member_model = Member::find()->where(['id'=>$uinfo['id']])->one();

        $model = Yii::$app->getSecurity()->validatePassword($password, $member_model['password_hash'] );
        if($model != 1){
            $this->error_message('_The_Password_Error_');
        }

        $varcode_result = Varcode::find()->where(['mobile_phone' => $phone, 'varcode'=>$varcode])->one();
        if (empty($varcode_result)){
             $this->error_message('手机验证码错误');

        }
         $var = Member::find()->where(['mobile_phone'=>$phone])->one();
         if(!empty($var)){
             $this->error_message('该手机号已经注册,请勿重复绑定');
         }
         $ret = Member::find()->where(['id'=>$uinfo['id']])->asArray()->one();
         //var_dump($ret);
         if(!empty($ret['mobile_phone'])){
             $this->error_message('你已经绑定过手机号了');
         }  

        $member = Member::findOne(['id'=>$uinfo['id']]);
        $member->mobile_phone =$phone;

        if($member->save()>0){
            $session = new Session;
            $session->open();
            $session['phone'] = $phone;  
            $this->update_user_level($uinfo['id']);
            $this->success_message('绑定手机号成功!');
        }                
     }


    // public function actionEmailCode(){
    //     $request = Yii::$app->request;
    //     $email = $request->post('email');
    //     $this->check_empty($email,'邮箱号不能为空');
    //     if(!preg_match("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.[a-zA-Z0-9]{2,6}$/",$email)){
    //         $this->error_message('邮箱不合法');
    //     }
    //     $var = Member::find()->where(['email'=>$email])->one();
    //     if(!empty($var)){
    //         $this->error_message('该邮箱已经注册,请勿重复注册');
    //     }
    // }

    public function actionMessageList(){
        $request = Yii::$app->request;
        $type = intval($request->post('type'));
        if ($type < 0) {
            $this->error_message('消息类型有误');
        }
        $access_token = $request->post('access_token');
        $uinfo = $this->memberToken($access_token);
        $where['status']=1;
        $where['type']=$type;
        if ($type == 1) {
            $where['uid'] = $uinfo['id'];
        }
        $result = Message::find()
                ->select(new Expression("title,content,from_unixtime(add_time,'%Y-%m-%d %H:%m') add_time"))
                ->where($where)->orderBy("id desc");
        $data = $this->actionCheckPage($result);
        $count = $result->count();
        if ($data) {
            $ret = ['code' => 200, 'count' => $count,'data' => $data, 'message' => 'success'];
            $this->do_aes(json_encode($ret));
            // $this->success_message($data,'_Success_');
        }else{
            $this->error_message('_No_Data_Query_');
        }
    }

    // 分页代码
    private function actionCheckPage($models){
        $request = Yii::$app->request;
        $count = $models->count();
        if($request->isPost){
            $limit_begin = $request->post('limit_begin');
            $limit_num = $request->post('limit_num');
            $limit_begin = empty($limit_begin)?0:$limit_begin;
            $limit_num = empty($limit_num)?intval($count):intval($limit_num);
        }
        $pages = new Pagination(['totalCount'=>$count,'pageSize'=>$limit_num]);
        $pages->setPage($limit_begin-1);

        $data = $models->offset($limit_begin)->limit($pages->limit)->asArray()->all();
        return $data;
    }


    public function actionMobileVarcode(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $uinfo = $os == 'web' ? $this->memberToken($access_token) : $this->checkToken($access_token);
       
    }
}