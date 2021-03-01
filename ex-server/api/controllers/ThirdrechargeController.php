<?php
namespace api\controllers;

use Yii;
use api\models\Coin;
use api\models\Transaction;
use api\models\BalanceLog;
use api\models\MemberWallet;
use common\jinglan\Bank;
use common\jinglan\Reward;
use common\jinglan\Trade;
use common\models\WithdrawApply;
use jinglan\walletapi\WalletRPC;

class ThirdrechargeController extends ApibaseController{

    public $modelClass = '';

    protected $_md5_key = '';

    public function init(){
        parent::init();
        $this->_md5_key = 'fa54723160b6d5ca88fae5736ade57df';
    }

    public function die_error($message='') {
        $ret = array(
            'status' => 0,
            'msg' => $message
        );
        die(json_encode($ret));
    }

    public function die_success($message='') {
        $ret = array(
            'status' => 1,
            'msg' => $message
        );
        die(json_encode($ret));
    }

    public function actionCharge(){
        $params = Yii::$app->request->post();

        //var_dump($params);

        if(!isset($params['sign'])){
            die_error('sign not set');
        }

        $sign = $params['sign'];
        
        unset($params['chain_network']);

        $params_final = $this->filterPara($params);

        $sign_str =  $this->buildRequestMysign($params_final);
        var_dump($sign_str);
        if ($sign_str !== $sign ) {
            $this->die_error('sign error');   
        }


        if (empty($params['order_no'])) {
            $this->die_error('order_no can not empty');
        }
        if (empty($params['source_usdt_addr'])) {
            $this->die_error('source_usdt_addr can not empty');
        }
        if (empty($params['target_usdt_addr'])) {
            $this->die_error('target_usdt_addr can not empty');
        }
        if (empty($params['amount'])) {
            $this->die_error('amount can not empty');
        }
        if (empty($params['currency_type'])) {
            $this->die_error('currency_type can not empty');
        }
        if ($params['currency_type'] == 'UDT') {
            $params['currency_type'] = 'USDT';
        }
        if (floatval($params['amount']) < 0.0001) {
            $this->die_error('amount error , min 0.0001');
        }


        $notify_type = 'thirdrecharge';
        $from_address= $params['source_usdt_addr'];
        $to_address= $params['target_usdt_addr'];
        $amount = $params['amount'];
        $blockhash = '';
        $txid = $params['order_no']; //链上交易ID
        $coin_symbol = $params['currency_type']; 
        if(isset($params['wallet_memo'])){
            $wallet_memo = $params['wallet_memo'];
        }else{
            $wallet_memo = '';
        }
        $memo = $params['order_no'];
        $walletData_all = MemberWallet::find()->where(['coin_symbol'=>'_'.$coin_symbol.'_','addr'=>$from_address,'memo'=>$wallet_memo])->asArray()->one();
//$query = MemberWallet::find()->where(['coin_symbol'=>'_'.$coin_symbol.'_','addr'=>$from_address,'memo'=>$wallet_memo]);
//echo $query->createCommand()->getRawSql();
//var_dump($walletData_all);die();
        if (empty($walletData_all)) {
            $this->die_error('source_usdt_addr can not find');
        }
        $this->turn_out($coin_symbol,$from_address,$to_address,$wallet_memo,$memo,$amount,$blockhash,$txid,$walletData_all['uid'],$walletData_all['seed']);
    }

    // 提现【从用户银行账户转到指定钱包地址】
    public function turn_out($coin_symbol,$from_address,$to_address,$wallet_memo,$memo,$amount,$blockhash,$txid,$uid,$from_address_id)
    {
        $log =   WithdrawApply::find()->where(['tx_hash'=>$txid])->asArray()->all();
        if($log){
            $this->die_error('order_no can not repeat');
        }

        // 1：币种合法性【不支持的币种不接受提现】
        $coins = Coin::find()->where(['symbol' => $coin_symbol])->andWhere(['enable' => 1])->one();
        if (!$coins) {
            $this->die_error('currency_type not allow');
        }

        // 获取用户资产
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($uid);// 成功返回数据，失败返回false
        if (!$balance_all) {
            $this->die_error('user balance is not enough');
        }
        foreach ($balance_all[0] as $key => $value) {
            if ($value['name'] == $coin_symbol) {
                if(empty($value['addr'])){
                    $this->die_error('user balance is not enough');
                }
                if ($value['available'] < $amount) {
                    $this->die_error('user balance is not enough');
                }
                break;
            }
        }

        // 存储提现申请表
        $withdraw_apply = new WithdrawApply();
        $withdraw_apply->member_id     = intval($uid);
        $withdraw_apply->coin_symbol   = $coin_symbol;
        $withdraw_apply->addr          = $to_address;
        $withdraw_apply->value_dec     = $amount;
        $withdraw_apply->current       = 0;
        $withdraw_apply->withdraw_fee  = 0;
        $withdraw_apply->description   = '第三方提币,订单号'.$memo;
        $withdraw_apply->tx_hash   = $txid;
        $withdraw_apply->status        = 2;//【1：待审核、2：审核通过、3：未通过】
        $withdraw_apply->created_at    = time();
        $withdraw_apply->chain_network = 0;
        if (!$withdraw_apply->save()) {
            $this->die_error('server busy');
        }

        $transaction = new Transaction;
        $transaction->type          = 3;// 1:钱包转账交易 2:存入银行 3:取出银行 4:场外交易
        $transaction->member_id     = $uid;
        $transaction->coin_symbol   = $coin_symbol;
        $transaction->from          = $from_address;
        $transaction->to            = $to_address;
        $transaction->value_hex     = '';
        $transaction->value_dec     = (string)$amount;
        $transaction->gas_hex       = '';
        $transaction->gas_dec       = '';
        $transaction->gas_price_hex = '';
        $transaction->gas_price_dec = '';
        $transaction->nonce_hex     = '0x0';
        $transaction->nonce_dec     = '0';
        $transaction->tx_status     = 'prepare';
        $transaction->network       = 1;
        if (!$transaction->save(false)) {
            $this->die_error('server busy');
        }
        $transaction_no = $transaction->attributes['id'];

        //api直接打款
        // 三、开始进行转出操作
        $proto = Yii::$app->config->info('WALLET_API_PROTOCAL');;
        $host = Yii::$app->config->info('WALLET_API_URL');;
        $port = Yii::$app->config->info('WALLET_API_PORT');;
        $_md5_key = Yii::$app->config->info('WALLET_API_KEY');; 

        $rpc = new WalletRPC($proto,$host,$port,$_md5_key );
        $rpc_ret = $rpc->account_transfer($from_address_id,$from_address,$amount,$to_address,$transaction_no);
//var_dump($rpc_ret);die();
        
        if ($rpc_ret['code'] == 0) {
        	
        	$this->die_success('success');
        }else{
            $tx_hash = $rpc_ret['data']['tx_id'];
            $find_transaction = Transaction::find()->where(['id'=> $transaction_no])->one();
            if($find_transaction){
                $find_transaction->tx_hash = $tx_hash;
                $find_transaction->tx_status = 'pending';
                $find_transaction->save(false);
            }  
        }
        $this->die_success('success');
    }























  public function add_transfer_log($coin_symbol,$from_address,$to_address,$wallet_memo,$memo,$amount,$blockhash,$txid){
        //先查找记录是否存在
        $log =   Transaction::find()->where(['tx_hash'=>$txid,'tx_status'=>'success'])->asArray()->all();
        if($log){
            $this->die_error('order_no can not repeat');
        }

        $walletData_all = MemberWallet::find()->where(['coin_symbol'=>'_'.$coin_symbol.'_','addr'=>$to_address,'memo'=>$wallet_memo])->asArray()->all();
        foreach ($walletData_all as $key => $walletData) {
            if(!empty($walletData))
                
                //var_dump($walletData);

                $transaction0 = Yii::$app->db->beginTransaction();
                // 数据库存储记录
                $transaction = new Transaction;
                $transaction->type          = 2;
                $transaction->member_id     = $walletData['uid'];
                $transaction->coin_symbol   = $coin_symbol;
                $transaction->from          = $from_address;
                $transaction->to            = $to_address;
                $transaction->value_hex     = '0x';
                $transaction->value_dec     = (string)$amount;
                $transaction->gas_hex       = '0x';
                $transaction->gas_dec       = '0';
                $transaction->gas_price_hex = '0x';
                $transaction->gas_price_dec = '0';
                $transaction->nonce_hex     = '0x0';
                $transaction->nonce_dec     = '0';
                $transaction->tx_hash     = $txid;
                $transaction->tx_status     = 'success';
                $transaction->network       = 0;
                $transaction->notify_status       = 0;
                if (!$transaction->save()) {
                    //var_dump($transaction);
                    $transaction0->rollBack();
                    die('Save Error#transaction');
                }
                $transaction_no = $transaction->attributes['id'];
                //加币
                $bank_balance2 = Bank::getBalance($walletData['uid'],$coin_symbol);
                $balance_model3 = new BalanceLog();
                $balance_model3->type = 1;//1:充值，10:取出
                $balance_model3->member_id = $walletData['uid'];
                $balance_model3->coin_symbol = $coin_symbol;
                $balance_model3->addr = $to_address;
                $balance_model3->change = (double)$amount;
                $balance_model3->balance = $bank_balance2 + (double)$amount;
                $balance_model3->fee = 0.0;
                $balance_model3->detial_type = 'system';
                $balance_model3->network = 0;

                if(!$balance_model3->save(false)){
                    $transaction0->rollBack();
                    die('add balace failed');
                }

                Reward::recharge($walletData['uid'],$coin_symbol,(double)$amount);

                $transaction0->commit();
        }
        $this->die_success('success');
    }

    public function confirm_transfer($coin_symbol,$from_address,$to_address,$wallet_memo,$memo,$amount,$blockhash,$txid){

        $logs = Transaction::find()->where(['tx_hash'=>$memo,'tx_status'=>'pending'])->all();
        //echo '|logs:'.count($logs);
        foreach ($logs as  $value) {
            $value->tx_hash = $txid;
            $value->tx_status = 'success';
            $value->save();
        }
        $logs2 = WithdrawApply::find()->where(['tx_hash'=>$memo,'status'=>5])->all();
        //echo '|logs:'.count($logs);
        foreach ($logs2 as  $value) {
            $value->status = 2;
            $value->save();
            Reward::withdraw($value->member_id,$value->coin_symbol,$value->value_dec);
        }        
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function paraFilter($para) {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if($key == "sign" )continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    public function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function createLinkstring($para) {
        $arg  = "";
        foreach ($para as $key => $val) {
            $arg.=$key."=".$val."|";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,strlen($arg)-1);
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }
        return $arg;
    }
    /**
     * 生成md5签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    public function md5Sign($prestr, $key) {
        $prestr = $prestr . '|'.$key;
        //var_dump($prestr);
        return md5($prestr);
    }

    public function filterPara($para_temp){
        $para_filter = $this->paraFilter($para_temp);//除去待签名参数数组中的空值和签名参数
        return $this->argSort($para_filter);//对待签名参数数组排序
    }
    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * @return string 签名结果字符串
     */
    public function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        $mysign = "";
        $mysign = $this->md5Sign($prestr, $this->_md5_key);

        return $mysign;
    }
    /**
     * 生成要发送的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    public function buildRequestPara($para_temp) {
        $para_sort = $this->filterPara($para_temp);//对待签名参数进行过滤
        $para_sort['sign'] = $this->buildRequestMysign($para_sort);//生成签名结果，并与签名方式加入请求提交参数组中
        return $para_sort;
    }    



    public function actionNotify() {

        $walletData_all = Transaction::find()->where(['notify_status'=>0])->asArray()->all();
        if (empty($walletData_all)) {
            $this->die_error('no order wait ...');
        }else{
            foreach ($walletData_all as $key => $walletData) {
                if(!empty($walletData)){
                    $order_no = $walletData['tx_hash'];
                    $tran_no = $walletData['id'];
                    $amount = $walletData['value_dec'];

                    //$host='https://www.happy150.com/api/send_notify_callback';
                    $host='https://api.abc88888.club/callback';
                    $postdata = array(
                        'order_no' => $order_no,
                        'tran_no' => $tran_no,
                        'amount' => $amount,
                        'status' => 1,
                    );
                    $postdata = $this->filterPara($postdata);
                    $sign_str =  $this->buildRequestMysign($postdata);
                    $postdata['msg'] = 'success';
                    $postdata['sign'] = $sign_str;
                    var_dump($postdata);
                    $ret = $this -> curl($host,$postdata);
                    var_dump($ret);
                    if ($ret == 'OK') {
                            $transaction = Transaction::findOne(['id'=>$walletData['id']]);
                            $transaction->notify_status = 1;
                            $transaction->notify_time = time();
                            $transaction->save();
                    }else{
                            $transaction = Transaction::findOne(['id'=>$walletData['id']]);
                            $transaction->notify_time = time();
                            $transaction->notify_response = $ret;
                            $transaction->notify_fail_num = $transaction->notify_fail_num + 1;
                            $transaction->save();
                    }
                }
            }
        }
        die('ok');
    }   

    public function actionRecharge_notify() {
        $walletData_all = Transaction::find()->where(['notify_status'=>0])->asArray()->all();
        if (empty($walletData_all)) {
            $this->die_error('no order wait ...');
        }else{
            foreach ($walletData_all as $key => $walletData) {
                if(!empty($walletData)){
                    $tran_no = $walletData['id'];
                    $source_usdt_addr = $walletData['tx_hash'];
                    $target_usdt_addr = $walletData['to'];
                    $currency_type = $walletData['coin_symbol'];
                    $amount = $walletData['value_dec'];
                    $send_at = date('YmdHis', $walletData['created_at']);
                    $remark = $walletData['tx_hash'];
                    
                    //$host='https://www.happy150.com/api/recv_notify';
                    $host='https://api.abc88888.club/recv_notify';
                    $postdata = array(
                        'tran_no' => $tran_no,
                        'source_usdt_addr' => $source_usdt_addr,
                        'target_usdt_addr' => $target_usdt_addr,
                        'currency_type' => $currency_type,
                        'amount' => $amount,
                        'send_at' => $send_at,
                    );
                    $postdata = $this->filterPara($postdata);
                    $sign_str =  $this->buildRequestMysign($postdata);
                    $postdata['remark'] = $remark;
                    $postdata['sign'] = $sign_str;
                    //var_dump($postdata);
                    $ret = $this -> curl($host,$postdata);
                    //
                    //var_dump($ret);
                    if ($ret == 'OK') {
                            $transaction = Transaction::findOne(['id'=>$walletData['id']]);
                            $transaction->notify_status = 1;
                            $transaction->notify_time = time();
                            $transaction->save();
                    }else{
                            $transaction = Transaction::findOne(['id'=>$walletData['id']]);
                            $transaction->notify_time = time();
                            $transaction->notify_response = $ret;
                            $transaction->notify_fail_num = $transaction->notify_fail_num + 1;
                            $transaction->save();
                    }
                }
            }
        }
        die('ok');
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
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$postdata);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在      
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     "Content-Type: application/json",
        // ]);
        $output = curl_exec($ch);
        $info   = curl_getinfo($ch);
        $err   = curl_errno($ch);
        curl_close($ch);
        //var_dump($err);
        return $output;
    }











}