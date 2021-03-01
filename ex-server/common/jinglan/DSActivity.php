<?php
/**
 * DS Activity 
 */

namespace common\jinglan;

use Yii;
use common\models\BalanceLog;
use api\models\Member;
use jinglan\ves\VesRPC;

class DSActivity extends Jinglan
{   


    protected static $open_status = 1;

    public static function regReward($uid){

        if(!self::$open_status){
            return ;
        }

        $start_uid = 1000463; //数据库起始UID

        //DSC
        $amount = 1000; //#DSC注册赠送数量
        $period = 36;//#释放周期
        $unit = 30; //#每个周期天数
        $max_num = 10000; //#活动最大人数
        $coin_symbol = "DSC"; //#DSC
        $type = 1; //线性释放

        if($uid - 1000463 <= $max_num){
            self::lock($uid,$coin_symbol,$amount,$period,$unit,$type);
        }


        //VDS
        $amount = 100;//#VDS注册赠送数量
        $period = 0;
        $unit = 0;
        $max_num = 10000;//#活动最大人数
        $coin_symbol = "VDS";//#VDS
        $type = 21; //按活动条件释放

        if($uid - $start_uid <= $max_num){
            self::lock($uid,$coin_symbol,$amount,$period,$unit,$type);
        }


    }


    public static function check_condition(){

        $time_now = time();
        $tablePrefix = Yii::$app->db->tablePrefix;
        $lists = (new \yii\db\Query())
            ->select('*')
            ->from("{$tablePrefix}member_activity")
            ->where(['status'=>1])
            ->andWhere(['type'=>21])
            //->andWhere(['>', 'next_release_time', $time_now])
            ->orderBy('id desc')
            ->All();    

        $coin = "VDS";
        $vds_price = self::coin_usd_price($coin);
        //var_dump($vds_price);
        $people_num = 10;
        $people_reward = 1;
        $deal_num = 500;
        $deal_reward = 1;

        foreach($lists as $item){
            //获取释放信息
            $extra =  $item['extra'];
            $extra_array=  array();
            if(!empty($extra)){
                $extra_array =  json_decode($extra,true);
            }
            if(isset($extra_array['invite_count'])){
                $old_invite_count = $extra_array['invite_count'];
            }else{
                $old_invite_count = 0;
            }

            if(isset($extra_array['deal_amount'])){
                $old_deal_amount = $extra_array['deal_amount'];
            }else{
                $old_deal_amount = 0;
            }

            if(isset($extra_array['deal_amount_usdt'])){
                $old_deal_amount_usdt = $extra_array['deal_amount_usdt'];
            }else{
                $old_deal_amount_usdt = 0;
            }

            if(isset($extra_array['deal_value'])){
                $old_deal_value = $extra_array['deal_value'];
            }else{
                $old_deal_value = 0;
            }

            $invite_count = self::get_user_invite_count($item['uid']);
            $deal_amount = self::get_user_deal_amount($item['uid'],$coin);
            $deal_amount_usdt = self::get_user_deal_amount_usdt($item['uid'],$coin);

            $deal_value = ($deal_amount - $old_deal_amount) * $vds_price + $deal_amount_usdt - $old_deal_amount_usdt + $old_deal_value ;

            $amount = 0;

            $new_c = intval($invite_count/$people_num);
            $old_c = intval($old_invite_count/$people_num);
            $add_c = $new_c - $old_c;
            if($add_c>0){
                $amount  = $amount + $add_c * $people_reward;
            }

            $new_v = intval($deal_value/$deal_num);
            $old_v = intval($old_deal_value/$deal_num);
            $add_v = $new_v - $old_v;
            if($add_v>0){
                $amount  = $amount + $add_v * $deal_reward;
            }

            //var_dump($invite_count);
            //var_dump($deal_amount);
            //var_dump($amount);
            if($amount>0){
                $left_amount =  $item['left_amount'];
                if($amount>$left_amount){
                    $amount = $left_amount;
                }

                $new_extra_array = array('invite_count' => $invite_count , 'deal_amount' => $deal_amount , 'deal_amount_usdt' => $deal_amount_usdt , 'deal_value' => $deal_value);
                $new_extra = json_encode($new_extra_array);

                $aid = $item['id'];
                $uid = $item['uid'];
                $coin_symbol = $item['coin_symbol'];
                $type = $item['type'];
                $memo = "锁仓释放#".$aid;
                self::release($aid,$uid,$coin_symbol,$amount,$memo,$type,$new_extra);
                $left_amount = $left_amount - $amount;
                
                if($left_amount > 0){
                    $status = 1;
                }else{
                    $status = 0;
                }

                $update = Yii::$app->db->createCommand()->update("{$tablePrefix}member_activity",
                    array(
                        'left_amount' => $left_amount,
                        'extra' => $new_extra,
                        'status' => $status,
                    ),
                    "id=".$aid
                )->execute();                
            }

        }          

    }

    public static function check_release(){

        $time_now = time();
        $tablePrefix = Yii::$app->db->tablePrefix;
        $lists = (new \yii\db\Query())
            ->select('*')
            ->from("{$tablePrefix}member_activity ")
            ->where(['status'=>1])
            ->andWhere(['type'=>1])
            ->andWhere(['<=', 'next_release_time', $time_now])
            ->orderBy('id desc')
            ->All();    

        foreach($lists as &$item){
            //获取释放信息
            $aid = $item['id'];
            $uid = $item['uid'];
            $coin_symbol = $item['coin_symbol'];
            $amount =  $item['left_amount']/$item['left_period'];
            $type = $item['type'];
            $memo = "锁仓释放#".$aid;
            self::release($aid,$uid,$coin_symbol,$amount,$memo,$type);
            $left_amount = $item['left_amount'] - $amount;
            $left_period = $item['left_period'] - 1;
            $next_release_time = $item['next_release_time']+ $item['unit'] * 24* 3600;
            if($left_period > 0){
                $status = 1;
            }else{
                $status = 0;
            }
            $update = Yii::$app->db->createCommand()->update("{$tablePrefix}member_activity",
                array(
                    'left_amount' => $left_amount,
                    'left_period' => $left_period,
                    'next_release_time' => $next_release_time,
                    'status' => $status,
                ),
                "id=".$aid
            )->execute();
        }          

    }

    public static function lock($uid,$coin_symbol,$amount,$period,$unit,$type=1){
        $tablePrefix = Yii::$app->db->tablePrefix;
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_activity", [    
            'uid' => $uid,
            'coin_symbol' => $coin_symbol,    
            'amount' => $amount,   
            'left_amount' => $amount,      
            'period' => $period,
            'left_period' => $period,    
            'unit' => $unit,
            'type' => $type,  
            'extra' => "",  
            'next_release_time' => time()+ $unit * 24* 3600,    
            'ctime' => time(),               
            'status' => 1,                                              
           ])->execute();
    }

    private static function release($aid,$uid,$coin_symbol,$amount,$memo,$type,$extra=""){
        $tablePrefix = Yii::$app->db->tablePrefix;
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_activity_log", [    
            'aid' => $aid,
            'uid' => $uid,    
            'coin_symbol' => $coin_symbol,   
            'amount' => $amount,      
            'memo' => $memo,
            'type' => $type,
            'extra' => $extra,      
            'log_time' => time(),
            'status' => 1,                                             
           ])->execute();

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
            $balance_log->change      = $amount;
            $balance_log->balance     = (float)$user_balance + $amount;
            $balance_log->fee         = 0;
            $balance_log->detial      = $uid;
            $balance_log->detial_type = 'release';
            $balance_log->ctime       = time();
            $balance_log->network     = $network;
            $balance_log->memo     = $memo;

            $balance_log->save();
        
    }

    private static  function get_user_invite_count($uid){
        //last_member  verified_status
        $count = Member::find()->where(['last_member'=>$uid,'verified_status'=>1])->count();
        //$count = Member::find()->where(['verified_status'=>1])->count();

        return $count;
    }


    private  static function get_user_deal_amount($uid,$coin_symbol){
        $data = (new \yii\db\Query())->from('jl_coins_order')->where(['uid' => $uid,"stock" =>$coin_symbol,"money" =>"USDT","side" => 1])->all();
        //$data = (new \yii\db\Query())->from('jl_coins_order')->where(["stock" =>$coin_symbol,"money" =>"USDT","side" => 1])->all();

        $amount = 0;
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $amount = $amount + $value["deal"];
            }
        }
        return $amount;
    }

    //USDT数量
    private  static function get_user_deal_amount_usdt($uid,$coin_symbol){
        $data = (new \yii\db\Query())->from('jl_coins_order')->where(['uid' => $uid,"stock" =>$coin_symbol,"money" =>"USDT","side" => 2])->all();
        //$data = (new \yii\db\Query())->from('jl_coins_order')->where(["stock" =>$coin_symbol,"money" =>"USDT","side" => 2])->all();

        $amount = 0;
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $amount = $amount + $value["deal"];
            }            
        }
         return $amount;
    }


    //获取交易对等值实时USD价格
    private static function coin_usd_price($coin_symbol){
        if($coin_symbol=="USDT"){
            return 1;
        }
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
            //var_dump($price_array);
            //获取当前币种价格
            //先判断是否存在USDT交易对
            if(isset($price_array[$coin_symbol."USDT"])){
                return $price_array[$coin_symbol."USDT"];
            }
            //遍历其他交易对
            foreach ($price_array as $key => $value) {
                if(strlen($key)>4){
                    if(substr($key, strlen($key)-4) == "USDT"){
                        $coin = substr($key, 0, strlen($key)-4);
                        if(isset($price_array[$coin_symbol.$coin])){
                            return $price_array[$coin_symbol.$coin] *$value;
                        }
                    }
                }
            }
            return 0;
        }else{
            return 0;
        }

    }

}