<?php
namespace api\controllers;

use Yii;
use api\models\BalanceLog;
use common\jinglan\Trade;


class VoteController extends ApibaseController{
    public $modelClass = '';

    public function init(){
        parent::init();
    }


    //获取币种列表
    public function actionInfo(){
        $tablePrefix = Yii::$app->db->tablePrefix;
        //未结束
        $vote_info = (new \yii\db\Query())
            ->from("{$tablePrefix}vote")
            ->where(['>', 'end_time', time()])
            ->andWhere(['>', 'coin_duihuan_num', 0])
            ->andWhere(['=', 'status', 1])
            ->All();

        //已经结束
        $vote_stopinfo = (new \yii\db\Query())
            ->from("{$tablePrefix}vote")
            ->where(['<', 'end_time', time()])
            ->orWhere(['<=', 'coin_duihuan_num', 0])
            ->andWhere(['=', 'status', 1])
            ->All();

        $data['no_stop'] = $vote_info;
        $data['stop'] = $vote_stopinfo;

        $this->success_message($data);
    }


    //获取币种列表
    public function actionInfobyid(){
        $request = Yii::$app->request;
        $vote_id = intval($request->post('id'));
        $tablePrefix = Yii::$app->db->tablePrefix;
        //未结束
        $vote_info = (new \yii\db\Query())
            ->from("{$tablePrefix}vote")
            ->where(['id'=>$vote_id])
            ->All();

        if (!$vote_info) {
            $this->error_message('_No_Data_Query_');
        }
        $vote_info = $vote_info[0];
        $vote_info['is_stop'] = 0;

        if (time() > $vote_info['end_time']) {
            $vote_info['is_stop'] = 1;
        }
        if ($vote_info['coin_duihuan_num'] <= 0) {
            $vote_info['is_stop'] = 1;
        }





        $this->success_message($vote_info);
    }




    //获取币种列表
    public function actionDuihuan(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->checkToken($access_token);
        $member_id = $uinfo['id'];

        $vote_id = intval($request->post('id'));
        $num = floatval($request->post('num'));
        if ($num <= 0) {
            $this->error_message('兑换数量有误');
        }

        $tablePrefix = Yii::$app->db->tablePrefix;
        $vote_info = (new \yii\db\Query())
            ->from("{$tablePrefix}vote")
            ->where(['status'=>1,'vote_status'=>2,'id'=>$vote_id])
            ->all();

        if (!$vote_info) {
            $this->error_message('_No_Data_Query_');
        }
        $vote_info = $vote_info[0];


        if (time() < $vote_info['start_time']) {
            $this->error_message('兑换未开始');
        }
        if (time() > $vote_info['end_time']) {
            $this->error_message('兑换已结束');
        }
        if ($vote_info['coin_duihuan_num'] <= 0) {
            $this->error_message('兑换已结束');
        }

        if ($num < $vote_info['coin_duihuan_min']) {
            $this->error_message('兑换数量过低');
        }
        if ($num > $vote_info['coin_duihuan_max']) {
            $this->error_message('兑换数量过大');
        }

        $ieo_balance = BalanceLog::find()
            ->select(['sum(`change`) as all_num'])
            ->where(['member_id' => intval($member_id)])
            ->andWhere(['coin_symbol' => $vote_info['coin_symbol']])
            ->andWhere(['detial_type' => 'ieo'])
            ->andWhere(['type' => 1])
            ->asArray()
            ->one();
        if ($ieo_balance) {
            if ((float)$ieo_balance["all_num"] >= (float)$vote_info['coin_duihuan_max']){
                $this->error_message('兑换数额超出个人兑换最大额度');
            }
        }




        $coin = $vote_info['coin_duihuan_money'];
        // $balance = BalanceLog::find()
        //     ->select(['balance', 'network'])
        //     ->where(['member_id' => intval($member_id)])
        //     ->andWhere(['coin_symbol' => $coin])
        //     ->orderBy('id DESC')
        //     ->one();

        // if ($balance) {
        //     //用户该地址在balance_log表中有记录
        //     $user_balance = $balance->balance;
        //     $network      = 0;
        // }else{
        //     $user_balance = 0;
        //     $network      = 0;
        //     //$this->error_message('_No_Data_Query_');            
        // }
        // 获取用户资产
        $user_balance  = 0;
        $network      = 0;
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($member_id);// 成功返回数据，失败返回false
        if (!$balance_all) {
            $this->error_message('_The_application_process_failed_unexpectedly_Please_try_again_later_');
        }
        foreach ($balance_all[0] as $key => $value) {
            if ($value['name'] == $coin) {
                $user_balance = $value['available'];
                break;
            }
        }


        $rate = floatval($vote_info['coin_duihuan_rate']);
        if ($rate <= 0) {
            $this->error_message('兑换有误');
        }


        $value_dec = $num*$rate;
        if ($value_dec > $user_balance) {
            $this->error_message('余额不足');
        }

        $change_type = 10;
        $addr = '';
        $memo = '';

        //扣币
        $change = intval($change_type)==1 ? (float)$value_dec : -(float)$value_dec;
        $balance_log              = new BalanceLog();
        $balance_log->type        = intval($change_type);
        $balance_log->member_id   = intval($member_id);
        $balance_log->coin_symbol = $coin;
        $balance_log->addr        = $addr;
        $balance_log->change      = $change;
        $balance_log->balance     = (float)$user_balance + $change;
        $balance_log->fee         = 0;
        $balance_log->detial      = $member_id.'-'.time().'-'.$addr;
        $balance_log->detial_type = 'ieo';
        $balance_log->ctime       = time();
        $balance_log->network     = $network;
        $balance_log->memo     = $memo;
        if ($balance_log->save()) {

            //加币
            $coin = $vote_info['coin_symbol'];
            $balance = BalanceLog::find()
                ->select(['balance', 'network'])
                ->where(['member_id' => intval($member_id)])
                ->andWhere(['coin_symbol' => $coin])
                ->orderBy('id DESC')
                ->one();
            if ($balance) {
                //用户该地址在balance_log表中有记录
                $user_balance = $balance->balance;
                $network      = 0;
            }else{
                //用户该地址在balance_log表中没记录
                $user_balance = 0;
                $network      = 0;
                //$this->error_message('_No_Data_Query_');            
            }

            $change_type = 1;
            $value_dec = $num;
            $change = intval($change_type)==1 ? (float)$value_dec : -(float)$value_dec;
            $balance_log              = new BalanceLog();
            $balance_log->type        = intval($change_type);
            $balance_log->member_id   = intval($member_id);
            $balance_log->coin_symbol = $coin;
            $balance_log->addr        = $addr;
            $balance_log->change      = $change;
            $balance_log->balance     = (float)$user_balance + $change;
            $balance_log->fee         = 0;
            $balance_log->detial      = $member_id.'-'.time().'-'.$addr;
            $balance_log->detial_type = 'ieo';
            $balance_log->ctime       = time();
            $balance_log->network     = $network;
            $balance_log->memo     = $memo;
            if ($balance_log->save()) {
                $update = Yii::$app->db->createCommand()->update("{$tablePrefix}vote", 
                  array(
                    'coin_duihuan_num' =>new \yii\db\Expression('coin_duihuan_num-'.$value_dec),
                    'coin_duihuan_waste' =>new \yii\db\Expression('coin_duihuan_waste+'.$value_dec),
                  ),
                  "id=".$vote_id
                )->execute();    

                $this->success_message('','兑换成功');
            }
        }else{
            $this->error_message('兑换失败');
  
        } 
        $this->error_message('兑换失败');
    }



    public function actionVote(){
        $request = Yii::$app->request;

        $access_token = $request->post('access_token');

        $vote_id = $request->post('vote_id');

        $side = $request->post('side');
        
        if(empty($side)){
            $this->error_message('side不能为空');
        }
        $tablePrefix = Yii::$app->db->tablePrefix;
    
        $vote_info = (new \yii\db\Query())
            ->select('id,vote_status')
            ->from("{$tablePrefix}vote")
            ->where(['status'=>1,'vote_status'=>2,'id'=>$vote_id])
            ->All();

        if (!$vote_info) {
            $this->error_message('_No_Data_Query_');
        }

        $coin = Yii::$app->config->info('PLATFORM_COIN_SYMBOL');

        $uinfo = $this->checkToken($access_token);
        $member_id = $uinfo['id'];

        $balance = BalanceLog::find()
            ->select(['balance', 'network'])
            ->where(['member_id' => intval($member_id)])
            ->andWhere(['coin_symbol' => $coin])
            ->orderBy('id DESC')
            ->one();

        if ($balance) {
            //用户该地址在balance_log表中有记录
            $user_balance = $balance->balance;
            $network      = 0;
        }else{
            $user_balance = 0;
            $network      = 0;
            //$this->error_message('_No_Data_Query_');            
        }

        $change_type = 10;
        $value_dec = 1;
        $addr = '';
        $memo = '';

        $change = intval($change_type)==1 ? (float)$value_dec : -(float)$value_dec;
        $balance_log              = new BalanceLog();
        $balance_log->type        = intval($change_type);
        $balance_log->member_id   = intval($member_id);
        $balance_log->coin_symbol = $coin;
        $balance_log->addr        = $addr;
        $balance_log->change      = $change;
        $balance_log->balance     = (float)$user_balance + $change;
        $balance_log->fee         = 0;
        $balance_log->detial      = $member_id.'-'.time().'-'.$addr;
        $balance_log->detial_type = 'vote';
        $balance_log->ctime       = time();
        $balance_log->network     = $network;
        $balance_log->memo     = $memo;
        if ($balance_log->save()) {

            Yii::$app->db->createCommand()->insert("{$tablePrefix}vote_log", [ 
                'side' => $side,
                'uid' => $member_id,    
                'vote_id' => $vote_id,   
                'time' => time(),     
               ])->execute();
                
            $id = Yii::$app->db->getLastInsertID();   

            $logs = (new \yii\db\Query())
                ->select('*')
                ->from("{$tablePrefix}vote_log ")
                ->where(['vote_id'=>$vote_id])
                ->All();
                     
            $uids = array();
            $vote_count = count($logs);
            $side1_count = 0;
            $side2_count = 0;  
            foreach ($logs as $key => $value) {
                if($value['side']==1){
                    $side1_count = $side1_count +1;
                }
                if($value['side']==2){
                     $side2_count = $side2_count +1;
                   
                }
                if(!in_array($value['uid'], $uids)){
                    $uids[] = $value['uid'];
                }                
            }
            $vote_rate = intval($side1_count*100/$vote_count);
            $update = Yii::$app->db->createCommand()->update("{$tablePrefix}vote", 
              array(
                'vote_count' => $vote_count, 
                'vote_num' => count($uids),  
                'vote_rate' => $vote_rate ,  
              ),
              "id=".$vote_id
            )->execute();     

            //重新查询投票情况
            $vote_info = (new \yii\db\Query())
            ->select('id,vote_count,vote_num,vote_rate,vote_status')
            ->from("{$tablePrefix}vote")
            ->where(['id'=>$vote_id])
            ->All();
                      
            $this->success_message($vote_info[0],'投票成功');
        }else{
            $this->error_message('投票失败');
  
        }           

    }

}
