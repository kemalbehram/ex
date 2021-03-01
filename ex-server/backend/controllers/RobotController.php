<?php

namespace backend\controllers;

use Yii;
use common\models\Robot;
use api\models\ExchangeCoins;
use yii\web\NotFoundHttpException;


class RobotController extends MController{

    public $STATUS = [
        0 => '关闭交易',
        1 => '开启交易'
    ];

    public $STATUS2 = [
        0 => '未开启',
        1 => '已开启'
    ];

    public $STATUS_COLOR = [
        0 => 'red',
        1 => '#1ab394',
    ];
    
    public function actionIndex(){
        $data = Robot::find()->joinwith('exchangeCoins')->all();
        foreach ($data as &$v){
          $v['intime'] = $this->time2string($v['intime']);
        }
        return $this->render('index', [
            'data' => $data,
            'status'  => $this->STATUS,
            'status2'  => $this->STATUS2,
            'status_color' => $this->STATUS_COLOR
        ]);
    }

    /**
     * @return string
     */
    public function actionEnable(){
        $request = Yii::$app->request;
        $id      = $request->post('id');
        $status  = $request->post('status');
        $model   = $this->findModel($id);
        $model->status = $status;
        if($model->save()){
            return json_encode(['code' => 200, 'message' => '操作成功']);
        }else{
            return json_encode(['code' => 201, 'message' => '操作失败']);
        }
    }

    /**
     * @return string
     */
    public function actionEnable2(){
        $request = Yii::$app->request;
        $id      = $request->post('id');
        $status  = $request->post('status');
        $model   = $this->findModel($id);
        $model->simulate_status = $status;
        if($status==1){
            if(empty($model->robot_set_content)){
                return json_encode(['code' => 201, 'message' => '请先配置模拟K线数据']);
            }
        }
        if($model->save()){
            return json_encode(['code' => 200, 'message' => '操作成功']);
        }else{
            return json_encode(['code' => 201, 'message' => '操作失败']);
        }
    }


    /**
     * @return string|\yii\web\Response
     */
    public function actionEdit(){
        $request = Yii::$app->request;
        $id  =  htmlspecialchars($request->get('id'));
        $model   = $this->findModel($id);
        if($model->load(Yii::$app->request->post())) {
            // $market_id = $_POST['ExchangeCoins']['id'];
            // p($model);exit();
            // p($model);exit();

            $model->otime = 0;
            $model->ctime = 0;
            $model->intime = 0;
            $model->simulate_status = 0;
            $model->robot_set_open = 0;
            $model->robot_set_close = 0;
            $model->robot_set_high = 0;
            $model->robot_set_low = 0;
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Coins model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if($this->findModel($id)->delete())
        {
            return $this->message("删除成功",$this->redirect(['index']));
        }
        else
        {
            return $this->message("删除失败",$this->redirect(['index']),'error');
        }
    }

    /**
     * Finds the Coins model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Coins the loaded model
     */
    protected function findModel($id)
    {
        if (empty($id)) {

            return new Robot();
        }

        if (empty($model = Robot::findOne($id))) {
            return new Robot();
        }

        return $model;
    }

    /**
     * 处理时间函数
     * @param $second
     * @return string
     */
    function time2string($second){
        $day = floor($second/(3600*24));
        $second = $second%(3600*24);
        $hour = floor($second/3600);
        $second = $second%3600;
        $minute = floor($second/60);
        $second = $second%60;
        if(strlen($second) == 1){
            $second = '0'.$second;
        }
        if($day == 0 && $hour == 0 && $minute == 0){
            return $second.'秒';
        }elseif($day == 0 && $hour == 0 && $minute != 0 && $second == 0){
            return $minute.'分钟';
        }elseif( $day == 0 && $hour == 0 ){
            return $minute.'分钟'.$second.'秒';
        }elseif( $day == 0){
            return $hour.'小时'.$minute.'分钟'.$second.'秒';
        }else{
            return $day.'天'.$hour.'小时'.$minute.'分钟'.$second.'秒';
        }

    }


    /**
     * @return string|\yii\web\Response
     */
    public function actionSimulate(){
        $request = Yii::$app->request;
        $id  =  htmlspecialchars($request->get('id'));
        $model   = $this->findModel($id);
        if($model->load(Yii::$app->request->post())) {
            // $market_id = $_POST['ExchangeCoins']['id'];
            // p($model);exit();
            // p($model);exit();

            // $model->market_id = $id;
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('simulate', [
            'model' => $model,
        ]);
    }

    public function actionInfo(){

        $request = Yii::$app->request;

        $access_token = $request->post('access_token');

        $uinfo = $this->checkToken($access_token);

        $where['id']= $uinfo['id'];

        $result = Member::find()->select("robot_coin_symbol,robot_set_open,robot_set_close,robot_set_high,robot_set_low,robot_set_content")->where($where)->asArray()->one();

        if($result){
            if(empty($result['robot_coin_symbol'])){
                $this->error_message('没有查询到数据');
            }
            $this->success_message($result,'查询成功');          
        }else{
            $this->error_message('没有查询到数据');
        }
    }

  public function fixNum($num,$base,$times,$set_min){
          $num = $set_min + ($num + $base - $set_min) * $times;
          return $num;
    }

    public function actionPreview(){


            $request = Yii::$app->request;

            $set_open = doubleval($request->post('set_open'));

            $set_close = doubleval($request->post('set_close'));

            $set_min = doubleval($request->post('set_min'));

            $set_max = doubleval($request->post('set_max'));

            if($set_open<=0||$set_close<=0||$set_min<=0||$set_max<=0){
                 $this->error_message('设定参数有误');
            }

            if($set_min>=$set_max){
                 $this->error_message('设定参数不合理1');
            }

            if($set_min>$set_open||$set_min>$set_close){
                 $this->error_message('设定参数不合理2');
            }            

            if($set_max<$set_open||$set_max<$set_close){
                 $this->error_message('设定参数不合理3');
            }

          //$set_open = 1001;
          //$set_close = 1150;
          //$set_min = 1000;
          //$set_max = 1200;

          $count = (new \yii\db\Query())->from('jl_history_ticker')->count();

          $fix_count = 10;

          $need_count = 1440 - $fix_count*2;


          if($count<=$need_count){
              $offset = 0;
          }else{
              $offset = rand(0, $count-$need_count);
          }

          //$offset = 0;

          $data = (new \yii\db\Query())->from('jl_history_ticker')->select('id,open,close,high,low,ctime')->offset($offset)->limit($need_count)->all();

          //数据标准化
          $min = 1000000000;
          $max = 0;


          foreach ($data as $key => $value) {
              $min = min($value['low'],$min);
              $max = max($value['high'],$max);
          }



          $base = $set_min - $min;
          $times = ($set_max -  $set_min ) / ($max - $min) ;

          foreach ($data as $key => &$value) {
              $value['open'] = $this->fixNum($value['open'],$base,$times,$set_min);
              $value['close'] = $this->fixNum($value['close'],$base,$times,$set_min);
              $value['high'] = $this->fixNum($value['high'],$base,$times,$set_min);
              $value['low'] = $this->fixNum($value['low'],$base,$times,$set_min);
          }

          $open =  $data[0]['open'];
          $close = $data[count($data)-1]['close'];
         
          $diff_open = $open - $set_open;

          $diff_close = $set_close - $close;


          for ($i=0; $i < $fix_count; $i++) {
               $fake_open =  $close + $i*$diff_close/$fix_count;
               $fake_close =  $close + ($i+1)*$diff_close/$fix_count;

               $item = array();
               $item['id'] = 0;
               $item['ctime'] = 0;               
               $item['open'] = $fake_open ;
               $item['close'] = $fake_close;
               $item['high'] = max($fake_open,$fake_close);
               $item['low'] = min($fake_open,$fake_close);
               array_push($data,$item);
          }

          for ($i=0; $i < $fix_count; $i++) {
               $fake_open =   $open  -  ($i+1)*$diff_open/$fix_count;
               $fake_close =  $open  -  $i*$diff_open/$fix_count;

               $item = array();
               $item['id'] = 0;
               $item['ctime'] = 0;               
               $item['open'] = $fake_open ;
               $item['close'] = $fake_close;
               $item['high'] = max($fake_open,$fake_close);
               $item['low'] = min($fake_open,$fake_close);
               array_unshift($data,$item);
          }

          //var_dump($open);
          //var_dump($close);
                  
          $list = array();

          $open = 0;
          $close = 0;
          $low = 1000000000;
          $high = 0;
          $i = 0;      
          $time = "";   

          $timeframe = 1;

          $index= 0;
          foreach ($data as $key => $value) {
            $i = $i + 1;      

            $low = min($value['low'],$low);
            $high = max($value['high'],$high);

            if($i == 1){
               $time = date("H:i:s",1514736000+$index*60);
               $open = $value['open'];
            }
            if($i == $timeframe ){
               $close = $value['close'];
               $item = array();
               $item[] = $time;
               $item[] = $open;
               $item[] = $close;
               $item[] = $low;
               $item[] = $high;               
               $list[] = $item; 
               $i= 0; 
               $low = 1000000000;
               $high = 0;                   
            } 
            $index = $index + 1;

          }

          //var_dump(count($list));
          $data_str = json_encode($list);

          $ret['offset'] = $offset;
          $ret['data'] = $data_str;

          $this->success_message($ret,'模拟成功');        

    }


    public function actionSubmit(){


            $request = Yii::$app->request;


            $id = doubleval($request->post('id'));

            $set_open = doubleval($request->post('set_open'));

            $set_close = doubleval($request->post('set_close'));

            $set_min = doubleval($request->post('set_min'));

            $set_max = doubleval($request->post('set_max'));

            $offset = intval($request->post('offset'));

            if($set_open<=0||$set_close<=0||$set_min<=0||$set_max<=0){
                 $this->error_message('设定参数有误');
            }

            if($offset<=0){
                 $this->error_message('设定offset参数有误');
            }

            if($set_min>=$set_max){
                 $this->error_message('设定参数不合理1');
            }

            if($set_min>$set_open||$set_min>$set_close){
                 $this->error_message('设定参数不合理2');
            }            

            if($set_max<$set_open||$set_max<$set_close){
                 $this->error_message('设定参数不合理3');
            }

          //$set_open = 1001;
          //$set_close = 1150;
          //$set_min = 1000;
          //$set_max = 1200;

          $count = (new \yii\db\Query())->from('jl_history_ticker')->count();

          $fix_count = 10;

          $need_count = 1440 - $fix_count*2;


          if($offset>=$count-$need_count){
               $this->error_message('设定offset参数有误');
          }

          $data = (new \yii\db\Query())->from('jl_history_ticker')->select('id,open,close,high,low,ctime')->offset($offset)->limit($need_count)->all();

          //数据标准化
          $min = 1000000000;
          $max = 0;


          foreach ($data as $key => $value) {
              $min = min($value['low'],$min);
              $max = max($value['high'],$max);
          }



          $base = $set_min - $min;
          $times = ($set_max -  $set_min ) / ($max - $min) ;

          foreach ($data as $key => &$value) {
              $value['open'] = $this->fixNum($value['open'],$base,$times,$set_min);
              $value['close'] = $this->fixNum($value['close'],$base,$times,$set_min);
              $value['high'] = $this->fixNum($value['high'],$base,$times,$set_min);
              $value['low'] = $this->fixNum($value['low'],$base,$times,$set_min);
          }

          $open =  $data[0]['open'];
          $close = $data[count($data)-1]['close'];
         
          $diff_open = $open - $set_open;

          $diff_close = $set_close - $close;


          for ($i=0; $i < $fix_count; $i++) {
               $fake_open =  $close + $i*$diff_close/$fix_count;
               $fake_close =  $close + ($i+1)*$diff_close/$fix_count;

               $item = array();
               $item['id'] = 0;
               $item['ctime'] = 0;               
               $item['open'] = $fake_open ;
               $item['close'] = $fake_close;
               $item['high'] = max($fake_open,$fake_close);
               $item['low'] = min($fake_open,$fake_close);
               array_push($data,$item);
          }

          for ($i=0; $i < $fix_count; $i++) {
               $fake_open =   $open  -  ($i+1)*$diff_open/$fix_count;
               $fake_close =  $open  -  $i*$diff_open/$fix_count;

               $item = array();
               $item['id'] = 0;
               $item['ctime'] = 0;               
               $item['open'] = $fake_open ;
               $item['close'] = $fake_close;
               $item['high'] = max($fake_open,$fake_close);
               $item['low'] = min($fake_open,$fake_close);
               array_unshift($data,$item);
          }

          //var_dump($open);
          //var_dump($close);
                  
          $list = array();

          $open = 0;
          $close = 0;
          $low = 1000000000;
          $high = 0;
          $i = 0;      
          $time = "";   

          $timeframe = 1;

          $index= 0;
          foreach ($data as $key => $value) {
            $i = $i + 1;      

            $low = min($value['low'],$low);
            $high = max($value['high'],$high);

            if($i == 1){
               $time = date("H:i:s",1514736000+$index*60);
               $open = $value['open'];
            }
            if($i == $timeframe ){
               $close = $value['close'];
               $item = array();
               $item[] = $time;
               $item[] = $open;
               $item[] = $close;
               $item[] = $low;
               $item[] = $high;               
               $list[] = $item; 
               $i= 0; 
               $low = 1000000000;
               $high = 0;                   
            } 
            $index = $index + 1;

          }

          //var_dump(count($list));
          $data_str = json_encode($list);

          $ret['offset'] = $offset;
          $ret['data'] = $data_str;


          $tablePrefix = Yii::$app->db->tablePrefix;

          $update = Yii::$app->db->createCommand()->update("{$tablePrefix}robot", 
            array(
              'robot_set_open' => $set_open,  
              'robot_set_close' => $set_close,  
              'robot_set_high' =>$set_max,  
              'robot_set_low' => $set_min,  
              'robot_set_content' => $data_str,
            ),
            "id=".$id
          )->execute();   
          if($update){
              $this->success_message($ret,'设置成功');    
          }else{
              $this->error_message('更新设置失败');               
          }

    }    


    //普通错误信息,客户端直接提示即可,客户端不需要对此状态吗做特殊处理
    protected function error_message($descrp='_Information_Wrong_'){
        $language = Yii::$app->request->post('language') == 'en_us'?'en_us':'zh_cn';
        if(is_string($descrp)){
            $ret = array('code'=>501,'message'=>Yii::t($language,$descrp));
        }else{
            $ret = array('code'=>501,'message'=>$descrp);
        }
        $this->do_aes(json_encode($ret));
    }

    //普通成功信息,统一格式
    protected function success_message($data='',$descrp = '_Submission_Success_'){
        $language = Yii::$app->request->post('language') == 'en_us'?'en_us':'zh_cn';
        if (empty($data)) {
            $ret = array('code'=>200,'message'=>Yii::t($language,$descrp));
        }else{
            $ret = array('code'=>200,'data'=>$data,'message'=>Yii::t($language,$descrp));
        }
        $this->do_aes(json_encode($ret));
    }

    //统一返回处理
    protected function do_aes($str){
        die($str);
    }

}
