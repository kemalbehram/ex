<?php
namespace api\controllers;

use api\models\MemberWallet;
use common\jinglan\Bank;
use api\models\BalanceLog;
use jinglan\ves\VesRPC;
use common\models\OtcCoinList;
use common\models\OtcOrder;
use common\models\OtcAppeal;
use common\helpers\StringHelper;
use common\helpers\FileHelper;
use yii\web\UploadedFile;
use yii\data\Pagination;
use Yii;
use common\jinglan\Trade;
use api\models\Coin;
use common\models\MemberProceeds;
use jinglan\sms\SMS;
use common\models\OtcMarket;
use api\models\Member;
use common\models\ApiAccessToken;
use linslin\yii2\curl;


class OtctaskController extends ApibaseController{
    public $modelClass = '';

    public function init(){
        parent::init();
    }

     //30分钟未付款自动取消订单
    //2小时未放币自动放币
    public function actionOrderCheck(){
        //超过30分钟未付款
        $tablePrefix = Yii::$app->db->tablePrefix;

        $appeals = OtcAppeal::find()->select('order_id,status')->asArray()->all();
        if (!empty($appeals)){
            $appeals = array_column($appeals,'status','order_id');
            $appeal_order_ids = array_keys($appeals);
        }else{
            $appeals = [];
            $appeal_order_ids = [];
        }
        //var_dump($appeal_order_ids);
        $time1 = date('Y-m-d H:i:s',time()-60*30);
        $orders = (new \yii\db\Query())
        ->select('id,buyer_uid')
        ->from("{$tablePrefix}otc_order ")
        ->where(['status'=>2])
        ->andWhere("order_time<'$time1'")
        ->All();

        //var_dump($orders);
        //检测是否有申诉
        foreach ($orders as $key => $order) {
            if(!in_array($order['id'],$appeal_order_ids)){                
                $buyer_uid = $order['buyer_uid'];
                $update = Yii::$app->db->createCommand()->update("{$tablePrefix}otc_order", 
                  array(
                    'deal_time' => date("Y-m-d H:i:s",time()), 
                    'status' => 0,  
                  ),
                  "id=".$order['id']
                )->execute();
                //var_dump($buyer_uid);die();
                $member_model = Member::findIdentity($buyer_uid);
                $member_model->otc_block_time = time()+3600*6;
                $member_model->save();                                   
            }
        }

        $time2 = date('Y-m-d H:i:s',time()-60*120);
        $orders2 = (new \yii\db\Query())
        ->select('id,buyer_uid,seller_uid')
        ->from("{$tablePrefix}otc_order ")
        ->where(['status'=>3])
        ->andWhere("pay_time<'$time2'")
        ->All();

        //var_dump($orders);
        //检测是否有申诉
        foreach ($orders2 as $key => $order) {
            if(!in_array($order['id'],$appeal_order_ids)){                
               //放币
                $uid = $order['seller_uid'];
                $order_id = $order['id'];
                $access_token = ApiAccessToken::find()->select(['access_token'])->where(['user_id'=>intval($uid)])->one();
                if (!empty($access_token)) {
                    // php发起curl请求api接口
                    $curl = new curl\Curl();
                    $url = Yii::$app->request->hostInfo.'/api/otc/deal';
                    $data = [
                        'access_token' => $access_token->access_token,
                        'order_id'     => $order_id,
                    ];
                    $response = $curl->setRequestBody(http_build_query($data))->post($url);     
                    //var_dump($response);                       
                }
                               
            }
        }

        die('check ok');
    }   

    //修改实名信息

    //扣除手续费统计 

    //小时设置

}
