<?php
namespace api\controllers;

use Yii;
use api\models\Transaction;
use api\models\BalanceLog;
use api\models\MemberWallet;
use common\jinglan\Bank;
use common\jinglan\Reward;
use common\jinglan\Trade;

class ThirdwalletController extends ApibaseController{

    public $modelClass = '';

    protected $_md5_key = '';

    public function init(){
        parent::init();
        $this->_md5_key = 'abc123456';
    }

    public function die_error($message='') {
        $ret = array(
            'code' => 0,
            'msg' => $message
        );
        die(json_encode($ret));
    }

    public function die_success($data = null) {
        $ret = array(
            'code' => 1,
            'msg' => 'success',
            'data' => $data
        );
        die(json_encode($ret));
    }
    
    public function actionCheck_address(){
        $params = Yii::$app->request->post();

        if(!isset($params['sign'])){
            $this->die_error('sign not set');
        }

        $sign = $params['sign'];

        $params_final = $this->filterPara($params);

        $sign_str =  $this->buildRequestMysign($params_final);
        // echo $sign_str;die;
        if ($sign_str !== $sign ) {
            $this->die_error('sign error');   
        }
        if (empty($params['coin'])) {
            $this->die_error('coin can not empty');
        }
        if (empty($params['addr'])) {
            $this->die_error('addr can not empty');
        }
        $coin_symbol = $params['coin'];
        $addr = $params['addr'];
        $walletData = MemberWallet::find()->where(['coin_symbol'=>'_'.$coin_symbol.'_','addr'=>$addr])->asArray()->one();
        if (empty($walletData)) {
            $this->die_error('addr can not find');
        }
        $data = [
            'uid' => $walletData['uid'],
            'coin_symbol' => $coin_symbol,
            'addr' => $walletData['addr']
        ];
        
        $this->die_success($data);
    }

    public function actionRecharge(){
        $params = Yii::$app->request->post();

        if(!isset($params['sign'])) {
            $this->die_error('sign not set');
        }

        $sign = $params['sign'];
       
        $params_final = $this->filterPara($params);

        $sign_str =  $this->buildRequestMysign($params_final);
        if ($sign_str !== $sign ) {
            $this->die_error('sign error');
        }
        
        if (empty($params['uid'])) {
            $this->die_error('uid can not empty');
        }
        if (empty($params['transaction_id'])) {
            $this->die_error('transaction_id can not empty');
        }
        if (empty($params['source_addr'])) {
            $this->die_error('source_addr can not empty');
        }
        if (empty($params['target_addr'])) {
            $this->die_error('target_addr can not empty');
        }
        if (empty($params['amount'])) {
            $this->die_error('amount can not empty');
        }
        if (empty($params['coin'])) {
            $this->die_error('coin can not empty');
        }
        if (floatval($params['amount']) < 0.0001) {
            $this->die_error('amount error , min 0.0001');
        }

        $uid = intval($params['uid']);
        $from_address= $params['source_addr'];
        $to_address= $params['target_addr'];
        $amount = $params['amount'];
        $txid = $params['transaction_id'];// 外部订单ID
        $coin_symbol = $params['coin'];
        $memo = empty($params['memo']) ? '' : $params['memo'];
        $walletData = MemberWallet::find()->where(['uid'=>$uid, 'coin_symbol'=>'_'.$coin_symbol.'_','addr'=>$to_address])->asArray()->one();
        if (empty($walletData)) {
            $this->die_error('target_addr not found');
        }
        $this->add_transfer_log($coin_symbol,$from_address,$to_address,$amount,$memo,$txid, $walletData);
    }

  public function add_transfer_log($coin_symbol,$from_address,$to_address,$amount,$memo,$txid, $walletData){
        //先查找记录是否存在
        $log =   Transaction::find()->where(['tx_hash'=>$txid,'tx_status'=>'success'])->asArray()->all();
        if($log){
            $this->die_error('transaction_id can not repeat');
        }
        
        $transaction0 = Yii::$app->db->beginTransaction();
        // 数据库存储记录
        $transaction = new Transaction;
        $transaction->type          = 5; // 第三方系统转入
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
            $transaction0->rollBack();
            $this->die_error('Save Error#transaction');
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
        $balance_model3->detial = $txid.'-'.$transaction_no;
        $balance_model3->detial_type = 'third_recharge';
        $balance_model3->network = 0;
        $balance_model3->memo = $memo;

        if(!$balance_model3->save(false)){
            $transaction0->rollBack();
            $this->die_error('add balace failed');
        }

        Reward::recharge($walletData['uid'],$coin_symbol,(double)$amount);

        $transaction0->commit();
        $this->die_success();
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