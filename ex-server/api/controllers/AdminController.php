<?php
namespace api\controllers;

use Yii;
use api\models\Transaction;
use api\models\BalanceLog;
use api\models\MemberWallet;
use common\jinglan\Bank;
use common\models\WithdrawApply;
use common\jinglan\Reward;
use common\jinglan\Trade;
use jinglan\ves\VesRPC;

class AdminController extends ApibaseController{

    public $modelClass = '';

    protected $key = '';

    public function init(){
        parent::init();
        $this->key = "89757";
    }

    public function actionAdd(){
        $params = Yii::$app->request->post();

        //var_dump($params);

        if(!isset($params['key'])){
            $ret = array('code'=>0,'msg'=>'key not set');
            die(json_encode($ret));
        }

        $key = $params['key'];
        
        if ($key !== $this->key ) {
            $ret = array('code'=>0,'msg'=>'key error');
            die(json_encode($ret));  
        }
        $uid = $params['uid'];
        $amount = floatval(trim($params['amount']));
        $txid = $params['tx_id']; //交易ID
        $coin_symbol = $params['coin_symbol']; 
        if(isset($params['wallet_memo'])){
            $wallet_memo = $params['wallet_memo'];
        }else{
            $wallet_memo = '';
        }
        $memo = $params['memo'];
        $result = $this->add_transfer_log($coin_symbol,$uid,$amount,$txid);
        if ($result['code']==0){
            $ret = array('code'=>0,'msg'=>$result['msg']);
            die(json_encode($ret));  
        }
        $ret = array('code'=>1,'msg'=>'ok');
        die(json_encode($ret));  
    }

  public function add_transfer_log($coin_symbol,$uid,$amount,$txid){

        //加币
        //$bank_balance2 = Bank::getBalance($uid,$coin_symbol);

        // 获取用户资产
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($uid);// 成功返回数据，失败返回false
        if (!$balance_all) {
             return array('code'=>0,'msg'=>'余额查询失败');
        }
        foreach ($balance_all[0] as $key => $value) {
            if ($value['name'] == $coin_symbol) {
                $bank_balance2 = $value['available'];
                $bank_balance = $value['bank_balance'];
                break;
            }
        }
        //return array('code'=>0,'msg'=>'余额不足:'.$bank_balance2);
        if($amount<0){
            if($bank_balance2+$amount<0){
                return array('code'=>0,'msg'=>'余额不足:'.$bank_balance2);
            }
            if($bank_balance+$amount<0){
                $transaction2 = Yii::$app->db->beginTransaction();
                try{
                    $lack = abs($amount) - $bank_balance;

                    $balance_model = new BalanceLog();
                    $balance_model->type = 1;//1:充值，10:取出
                    $balance_model->member_id = $uid;
                    $balance_model->coin_symbol =$coin_symbol;
                    $balance_model->addr = "";
                    $balance_model->change = $lack;
                    $balance_model->balance = $bank_balance + $lack;
                    $balance_model->fee = 0.0;
                    $balance_model->detial_type = 'exchange';
                    $balance_model->network = 0;

                    if(!$balance_model->save(false)){
                        $transaction->rollBack();
                        $this->error_message('_Try_Again_Later_');
                    }
                    //更新交易所余额
                    $rpc = new VesRPC();
                    $rpc_ret = $rpc->do_rpc('balance.update', [intval($uid),$coin_symbol,"trade",$balance_model->attributes['id'],strval(-(float)$lack),['id'=>$balance_model->attributes['id']]]);
                    if ($rpc_ret['code'] == 0) {
                        $transaction2->rollBack();
                        $this->error_message($rpc_ret['data']);
                    } else {//更新成功
                        $transaction2->commit();
                    }
                }catch (\Exception $e){
                    $transaction->rollBack();
                    $this->error_message($e->getMessage());
                }                
            }
        }
        
        $bank_balance = Bank::getBalance($uid,$coin_symbol);
        
        $balance_model3 = new BalanceLog();
        if($amount>0){
            $balance_model3->type = 1;//1:充值，10:取出
        }else{
            $balance_model3->type = 10;
        }
        $balance_model3->member_id = $uid;
        $balance_model3->coin_symbol = $coin_symbol;
        $balance_model3->addr = $to_address;
        $balance_model3->change = (double)$amount;
        $balance_model3->balance = $bank_balance + (double)$amount;
        $balance_model3->fee = 0.0;
        $balance_model3->detial_type = 'transfer';
        $balance_model3->network = 0;

        if(!$balance_model3->save(false)){
            $transaction0->rollBack();
            return array('code'=>0,'msg'=>'add balance failed');
        }
        return array('code'=>1,'msg'=>'');
   
    }

}
