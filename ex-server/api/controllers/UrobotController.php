<?php
namespace api\controllers;

use Yii;
use common\models\member\Member;


class UrobotController extends ApibaseController{

    public $modelClass = '';

    public function init(){
        parent::init();
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

            $access_token = $request->post('access_token');

            $uinfo = $this->checkToken($access_token);

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

            $access_token = $request->post('access_token');

            $uinfo = $this->checkToken($access_token);

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

          $update = Yii::$app->db->createCommand()->update("{$tablePrefix}member", 
            array(
              'robot_set_open' => $set_open,  
              'robot_set_close' => $set_close,  
              'robot_set_high' =>$set_max,  
              'robot_set_low' => $set_min,  
              'robot_set_content' => $data_str,
            ),
            "id=".$uinfo['id']
          )->execute();   
          if($update){
              $this->success_message($ret,'设置成功');    
          }else{
              $this->error_message('更新设置失败');               
          }

    }    



}
