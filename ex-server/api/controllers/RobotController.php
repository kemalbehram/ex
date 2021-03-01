<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/11
 * Time: 14:36
 */

namespace api\controllers;

use Yii;
use common\models\Robot;
use api\models\ExchangeCoins;
use common\models\member\Member;
use jinglan\ves\VesRPC;
use common\jinglan\Jinglan;
use common\jinglan\Reward;

class RobotController extends ApibaseController
{
    public $modelClass = '';

    public function init(){
        parent::init();
    }

    public function actionRobotList()
    {
        $this->actionUpdateSelfRobot2();
        $host=$this->actionGetHost().'/third-party/huobi.php';
        $host='https://www.sucoin.cc/third-party/huobi.php';
        $ret = $this -> curl($host);
        $ret2 = json_decode($ret);

	    foreach ($ret2 as $key => $val) {
	        if(strlen($key)>4){
	            if(substr($key,-4)=='usdt'){
	                $new_key = substr($key,0,strlen($key)-1);
	                $ret2->$new_key = $ret2->$key;
	            }
	        }
	    }
        //var_dump($ret2);die();
        $exchange_coins_list = ExchangeCoins::find()->select(['id','stock','money'])->asArray()->all();
        foreach($ret2 as $key => $val){
            foreach($exchange_coins_list as $k=>$v){
                if ($key == strtolower($v['stock'].$v['money'])) {
                    $robot = Robot::find()->where(['market_id'=>$v['id']])->one();
                    if(empty($robot)){
                      continue;
                    }
                    $big_money = $val->max_price;
                    $small_money = $val->min_price;

                    $robot->big_money = $big_money*1.0001; // 1.001
                    $robot->small_money = $small_money*0.9999; // 0.999
                    if($big_money>100){
                        $robot->big_money = $big_money*1.001;
                        $robot->small_money = $small_money*0.999;                      
                    }
                    if($big_money>1000){
                        $robot->big_money = $big_money*1.0001;
                        $robot->small_money = $small_money*0.9999; 
                    }
                    $robot->save();
                    //var_dump($key);              
                    //var_dump($big_money);
                }
            }
        }
    }
 
      public function actionUpdateSelfRobot2()
    {

        $robots = Robot::find()->where(['simulate_status'=>1])->all();
        foreach($robots as $k=>$v){
            //var_dump($v);
            $time1 = strtotime(date("Y-m-d"));
            $time2 = time();
            $now_minutes =intval(($time2 - $time1)/60);

            $price = json_decode($v['robot_set_content']);
            if(isset($price[$now_minutes])){
                $p = $price[$now_minutes][2];
                $big_money = $p;
                $small_money = $p;
                //var_dump($p);
                $v->big_money = $big_money*1.001;
                $v->small_money = $small_money*0.999;
                $v->save();
            }

        }
        //die();
    }


     public function actionUpdateSelfRobot()
    {




        $ret2 = Member::find()->select("robot_coin_symbol,robot_set_open,robot_set_close,robot_set_high,robot_set_low,robot_set_content")->where("LENGTH(robot_coin_symbol)>0")->asArray()->all();

        //var_dump($ret2);die();

        $exchange_coins_list = ExchangeCoins::find()->select(['id','stock','money'])->asArray()->all();
        foreach($ret2 as $key => $val){

            foreach($exchange_coins_list as $k=>$v){
    
                if ($val['robot_coin_symbol'] == $v['stock'].'/'.$v['money']) {
                    //var_dump($val['robot_coin_symbol']);
                    //var_dump($v['stock'].'/'.$v['money']);
                    $robot = Robot::find()->where(['market_id'=>$v['id']])->one();
                    if(empty($robot)){
                      continue;
                    }
                   // var_dump($robot);die();
                    $time1 = strtotime(date("Y-m-d"));
                    $time2 = time();
                    $now_minutes =intval(($time2 - $time1)/60);

                    $price = json_decode($val['robot_set_content']);
                    if(isset($price[$now_minutes])){


                        $p = $price[$now_minutes][2];
                        $big_money = $p;
                        $small_money = $p;
                        //var_dump($p);
                        $robot->big_money = $big_money*1.001;
                        $robot->small_money = $small_money*0.999;
                        $robot->save();

                    }

                    //var_dump($key);              
                    //var_dump($big_money);
                }
            }
        }
    }


    //update 第一个参数:表名 第二个参数 :要修改为的数据 第三个数据:修改条件
    public function actionUp_rate()
    {
        $host='http://op.juhe.cn/onebox/exchange/query?key=6441bc5cc96078b6621b839da7c68520';
        $ret = $this -> curl($host);
        $ret2 = json_decode($ret);
        $data = $ret2->result->list;

        foreach($data as $key => $val){
            if ($val[0] == '美元') {

                    $rate = round($val[2]/$val[1],2);
                    if (($rate < 5) || ($rate > 10)) {
                        die('fail');
                    }
                    $tablePrefix = Yii::$app->db->tablePrefix;
                    $update = Yii::$app->db->createCommand()->update("{$tablePrefix}extension",
                      array(
                        'detial' => $rate,
                      ),
                      "`type`='usd_to_cny'"
                    )->execute();       
                    if ($update) {
                        die('up success');
                    }else{
                        die('fail');
                    }  
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
  

    public function curl($url, $postdata = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在      
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
        ]);
        $output = curl_exec($ch);
        $info   = curl_getinfo($ch);
        curl_close($ch);
        return $output;
    }

    //全部撤销某一个交易对
    public function actionCancel_all() {
        $request      = Yii::$app->request;
        $market = $request->post('market');
        $access_token = $request->post('access_token');
        $uinfo        = $this->checkToken($access_token);
        if (empty($market)) {
            $this->error_message('请求有误');
        }

        $rpc = new VesRPC();
        $rpc_ret = $rpc->do_rpc('market.list', []);
        if($rpc_ret['code'] == 1){
            $result = $rpc_ret['data'];
            foreach($result as $name){
                // 撤单
                $name = $name["name"];
                if ($name == $market) {
                    for($i=0;$i<10;$i++){
                        $this->cancel_all_order($name,(int)$uinfo['id'],$i*100);
                    }
                }
            };
        }
        $this->success_message('请求成功');
    }

    function cancel_all_order($market = 'ADAUSDT',$uid=1000463,$start_num=0) {
        $rpc = new VesRPC();
        $rpc_params = [intval($uid), $market, $start_num, 100];
        $rpc_ret = $rpc->do_rpc('order.pending', $rpc_params);
        if($rpc_ret['code'] == 1){
            $result = $rpc_ret['data']["records"];
            foreach($result as $name){
                $order_id = $name["id"];
                $rpc_params = [intval($uid), $market, intval($order_id)];
                $rpc = new VesRPC();
                $rpc_ret = $rpc->do_rpc('order.cancel', $rpc_params);
                if($rpc_ret['code'] == 1){
                    //echo '撤单'.$market.'编号'.$order_id.'成功<br/>';
                }
            };
        }
    }

    function cancel_order($market = 'ADAUSDT',$uid=1000463,$start_num=0) {
        $rpc = new VesRPC();
        $rpc_params = [intval($uid), $market, $start_num, 100];
        $rpc_ret = $rpc->do_rpc('order.pending', $rpc_params);
        if($rpc_ret['code'] == 1){
            $result = $rpc_ret['data']["records"];
            foreach($result as $name){
                //判断是5分钟前的
                $jiange = time()-$name["ctime"];
                if($jiange < 600){
                    echo 'time '.(int)$jiange.' jump<br/>';
                    continue;
                }
                // 撤单5分钟前的单
                $order_id = $name["id"];
                $rpc_params = [intval($uid), $market, intval($order_id)];
                $rpc = new VesRPC();
                $rpc_ret = $rpc->do_rpc('order.cancel', $rpc_params);
                if($rpc_ret['code'] == 1){
                    $update = Yii::$app->db->createCommand()->update("jl_coins_order",
                                                                array(
                                                                    'status' => 2,
                                                                ),
                                                                "order_id=".$order_id
                                                            )->execute();    
                    echo '撤单'.$market.'编号'.$order_id.'成功<br/>';
                }
            };
        }
    }

    function actionCo2() {
        $rpc = new VesRPC();
        $rpc_ret = $rpc->do_rpc('market.list', []);
        if($rpc_ret['code'] == 1){
            $result = $rpc_ret['data'];
            foreach($result as $name){
                // 撤单
                $name = $name["name"];
                echo '开始撤交易对'.$name.'<br />';
                for($i=0;$i<10;$i++){
                    $this->cancel_order($name,1002383,$i*100);
                }
            };
        }
        die('END');
    }
  
    function actionCo3() {
        //查所有交易对
        $exchange_coins_list = ExchangeCoins::find()->select(['id','stock','money'])->asArray()->all();
      
        $rpc = new VesRPC();
        $rpc_ret = $rpc->do_rpc('market.list', []);
        if($rpc_ret['code'] == 1){
            $result = $rpc_ret['data'];
            foreach($result as $name){
                //查机器人id
                foreach($exchange_coins_list as $k=>$v){
                    if(($name['money'] == $v['money'])&&($name['stock'] == $v['stock'])){
                        $robot = Robot::find()->where(['market_id'=>$v['id']])->one();
                        if(empty($robot)){
                          $robot_uid = 1000463;
                        }else{
                          $robot_uid = $robot->uid;
                        }
                        echo 'robot id is'.$robot_uid;
                    }
                }

                $name = $name["name"];
                echo '开始撤交易对'.$name.'<br />';
                for($i=0;$i<10;$i++){
                    $this->cancel_order($name,$robot_uid,$i*100);
                }
            };
        }
        die('END');
    }

    function actionUp_fee(){
        $where = [
            'or',
            ['=', 'status', 0],
            ['=', 'status', 2],
        ];        
        $data = (new \yii\db\Query())->from('jl_coins_order')->where($where)->orderby('utime asc')->all();
        if(empty($data)){
            die('no order wait ...');
        }
        foreach($data as $v){
            $this->order_detail($v['order_id'],$v['status']);
        }
        die('ok');
    }


    function order_detail($order_id,$order_status) {
        $rpc = new VesRPC();
        $rpc_params = [intval($order_id), 0, 100];
        $rpc_ret = $rpc->do_rpc('order.deals', $rpc_params);

        if($rpc_ret['code'] == 1){
            $result = $rpc_ret['data']["records"];
            if (empty($result)) {
                if($order_status == 2){
                    $status = 1;
                }else{
                    $status = 0;
                }
                $update = Yii::$app->db->createCommand()->update("jl_coins_order",
                                                                    array(
                                                                        'utime' => time(),
                                                                        'status' => $status,
                                                                    ),
                                                                    "order_id=".$order_id
                                                                )->execute();
                if($order_status == 2){
                    echo "wait(cancel finish)...";
                }else{
                    echo "wait ...";                    
                }                
            }else{
                $amount = 0;
                $fee = 0;
                $deal = 0;
                $status = 0;
                foreach($result as $v){
                    $amount = $amount + $v["amount"];
                    $fee = $fee + $v["fee"];
                    $deal = $deal + $v["deal"];
                    $role = $v["role"];
                };
              	$data_order = (new \yii\db\Query())->from('jl_coins_order')->where(['order_id' => $order_id])->one();
                if(floatval($data_order["amount_all"]) == floatval($amount)||$order_status == 2){
                	$status = 1;
                	if($data_order["side"]==2){//卖
                	    Reward::order(intval($data_order["uid"]),$data_order["stock"],$amount);
                	}else{
                	    Reward::order(intval($data_order["uid"]),$data_order["stock"],$amount);
                	}
                }
              
                $update = Yii::$app->db->createCommand()->update("jl_coins_order",
                                                                array(
                                                                    'amount' => $amount,
                                                                    'fee' => $fee,
                                                                    'deal' => $deal,
                                                                    'role' => $role,
                                                                    'utime' => time(),
                                                                    'status' => $status,
                                                                ),
                                                                "order_id=".$order_id
                                                            )->execute();
            }


        }
    }




    
}
