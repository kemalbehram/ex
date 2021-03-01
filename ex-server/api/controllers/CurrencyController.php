<?php
/**
 * Created by PhpStorm.
 * User: op
 * Date: 2018-05-29
 * Time: 19:26
 */

namespace api\controllers;

use api\models\Transaction;
use Yii;
use api\models\Coin;
use common\models\Versions;
use jinglan\ethereum\EthereumRPC;
use api\models\MemberWallet;
use yii\data\Pagination;
use jinglan\bitcoin\Balance;
use common\models\TransactionBtc;
use yii\db\Query;
use common\models\Coins;
use Denpa\Bitcoin\Client as BitcoinClient;


class CurrencyController extends ApibaseController
{
    public $modelClass = '';

    public function init(){
        parent::init();
    }

    /**
     * 获取余额
     */
    public function actionBalance(){
        $request = Yii::$app->request;

        $access_token = $request->post('access_token');
        $uinfo = $this->checkToken($access_token);

        $language = $request->post('language');
        $select = $language == 'en_us' ? 'usd' : 'cny';

        $coin_symbol = strtoupper($request->post('coin_symbol'));
        $coin_addr = $request->post('wallet_addr');

        $this->check_empty($coin_addr,'_MoneyAddress_Not_Empty_');
        $this->check_empty($coin_symbol,'_MoneyType_Not_Empty_');

        $coins = Coin::find()->select("id,symbol,".$select)->where(['symbol'=>$coin_symbol, 'enable'=>1])->asArray()->one();

        if(empty($coins)){
            $this->error_message('_MoneyType_Wrong_');
        }
        switch ($coins['symbol']){
            case 'BTC':
                $btc_balance = new Balance();
                $rpc_ret = $btc_balance->getbalance($coin_addr);

                if($rpc_ret['code'] == 0){
                    $this->error_message($rpc_ret['data']);
                }else{
                    $balance = $rpc_ret['data'];
                }
                break;
            case 'ETH':
                $coin_addr = strtolower($coin_addr);
                $rpc_method = 'eth_getBalance';
                $rpc_params = [$coin_addr, "latest"];
                $rpc = new EthereumRPC($rpc_method, $rpc_params);
                $rpc_ret = $rpc->do_rpc();
                if($rpc_ret['code'] == 0){
                    $this->error_message($rpc_ret['data']);
                }else{
                    $balance = hexdec($rpc_ret['data']) / 1000000000000000000;
                    $balance = $rpc->sctonum($balance);
                }
                break;
            default:
                $this->error_message(['获取'.$coins['symbol'].'_Balance_Be_Developed_']);
                break;
        }

        $ret = ['coin_symbol'=>$coin_symbol,'addr'=>$coin_addr,'balance'=>$balance,'exchange_rate'=>$coins[$select]];
        $this->update_balance($coin_symbol, $coin_addr, $balance);

        $this->success_message($ret);
    }

    /**
     * 根据地址查询转账记录
     */
    public function actionRecord(){
        $request = Yii::$app->request;
        $coin_addr = $request->post('wallet_addr');
        $coin_symbol = strtoupper($request->post('coin_symbol'));
        $language = $request->post('language');
        $page = $request->post('page');
        $page = empty($page) ? 1 : $page;
        $this->check_empty($coin_addr, '_Address_Not_Empty_');
        $this->check_empty($coin_symbol, '_Currency_Sign_Not_Empty_');
        $language = $language == 'en_us' ? 'usd' : 'cny';
        $s = $language == 'en_us' ? '$' : 'NT$';

        switch ($coin_symbol) {
            case 'BTC':
                $models = TransactionBtc::find()->with('coin')
                    ->where(['or', ['from' => $coin_addr], ['to' => $coin_addr]])
                    ->andWhere(['in' , 'tx_status', ['pending','success']])
                    ->andWhere(['coin_symbol' => $coin_symbol])
                    ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block');
                break;
            default:
                $coin_addr = strtolower($coin_addr);
                $models = Transaction::find()->with('coin')
                    ->where(['or', ['from' => $coin_addr], ['to' => $coin_addr]])
                    ->andWhere(['in' , 'tx_status', ['pending','success']])
                    ->andWhere(['coin_symbol' => $coin_symbol])
                    ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block');
                break;
        }
        $count = $models->count();
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' =>$this->_pageSize]);
        $pages->setPage($page-1);
        $maxPage = $pages->getPageCount();
        $data = $models->offset($pages->offset)->limit($pages->limit)
            ->orderBy('created_at desc')->asArray()->all();

        // 新增返回数据字段【货币图标：coin_icon,货币单位：coin_unit，交易确认数：confirmation_number】
        $coin = Coins::find()->where(['symbol' => $coin_symbol])->one();
        $coin_icon = $coin->icon ?? '';
        if ($coin_icon) {
            $coin_icon = parent::get_user_avatar_url($coin_icon);
        }
        $coin_unit = $coin->unit ?? '';

        // 获取当前块高度[BTC, ETH]
        switch ($coin_symbol) {
            case 'BTC':
                $bitcoind = new BitcoinClient();
                $btc_req = $bitcoind->request('getblockcount',[]);
                if($btc_req['code'] == 0){
                    $block_number = 1355536;
                }else{
                    $block_number = $btc_req['data']->get();
                }
                break;
            
            default:
                $rpc_method = 'eth_blockNumber';
                $rpc_params = [];
                $rpc = new EthereumRPC($rpc_method, $rpc_params);

                $rpc_ret = $rpc->do_rpc();

                if($rpc_ret['code'] == 0){
                    $block_number = 5954635;
                }else{
                    $block_number = hexdec($rpc_ret['data']);
                }
                break;
        }
        if(!empty($data)){
            foreach($data as $key => $item){
                $data[$key]['created_at'] = date('Y-m-d H:i:s', $item['created_at']);
                $rate = $item['coin'][$language];
                // 插入新增字段
                $data[$key]['coin_icon'] = $coin_icon;
                $data[$key]['coin_unit'] = $coin_unit;
                $data[$key]['confirmation_number'] = $block_number-$item['block'];


                if($item['from'] == $coin_addr){
                    $data[$key]['money'] = $s . sprintf('%.2f', $item['value_dec'] * $rate);
                    $data[$key]['value_dec'] = '-' . $item['value_dec'];
                }elseif($item['to'] == $coin_addr){
                    $data[$key]['money'] = $s . sprintf('%.2f', $item['value_dec'] * $rate);
                    $data[$key]['value_dec'] = '+' . $item['value_dec'];
                }
                unset($data[$key]['from']);
                unset($data[$key]['to']);
                unset($data[$key]['coin']);
            }
            $ret = ['code' => 200, 'maxPage' => $maxPage, 'data' => $data, 'message' => 'success'];
            $this->do_aes(json_encode($ret));
        }else{
            $this->error_message('_No_Data_Query_');
        }
    }

    /**
     * 查询用户转账记录
     */
    public function actionGetRecord(){
        $request = Yii::$app->request;
        $uid = Yii::$app->user->identity->user_id;
        $language = $request->post('language');
        $page = $request->post('page');
        $type = (string)$request->post('type');
        $page = empty($page) ? 1 : $page;
        $language = $language == 'en_us' ? 'usd' : 'cny';
        $select = $language == 'en_us' ? 'usd' : 'cny';
        $s = $language == 'en_us' ? '$' : 'NT$';
        $coins = Coin::find()->select("id,symbol,".$select)->where(['enable'=>1])->asArray()->all();

        if(empty($coins)){
            $this->error_message('_MoneyType_Wrong_');
        }
        $coins = array_column($coins,NULL,'symbol');

        $addrs = MemberWallet::find()->where(['uid' => $uid])->select(['addr','coin_symbol'])->distinct()->asArray()->all();
        // 将用户银行账户钱包地址去除
        foreach ($addrs as $key => $value) {
            if (substr($value['coin_symbol'], 0, 1) == '_') {
                unset($addrs[$key]);
            }else{
                unset($addrs[$key]['coin_symbol']);
            }
        }
        $addrs = array_unique(array_column($addrs, 'addr'));

        if($type==='1'){
        $models = Transaction::find()
            ->where(['or', ['in', 'from', $addrs], ['in', 'to', $addrs]])
            ->andWhere(['in' , 'tx_status', ['pending','success']])
            ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block')->orderBy('created_at desc');
        $models_2 = TransactionBtc::find()
            ->where(['or', ['in', 'from', $addrs], ['in', 'to', $addrs]])
            //->andWhere(['tx_status' => 'success'])
            ->andWhere(['in' , 'tx_status', ['pending','success']])
            ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block')->orderBy('created_at desc');
        }
        else if($type=='2'){
        $models = Transaction::find()
                ->where(['in', 'from', $addrs])
                ->andWhere(['in' , 'tx_status', ['pending','success']])
                ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block')->orderBy('created_at desc');
        $models_2 = TransactionBtc::find()
                ->where(['in', 'from', $addrs])
                //->andWhere(['tx_status' => 'success'])
                ->andWhere(['in' , 'tx_status', ['pending','success']])
                ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block')->orderBy('created_at desc');
        }
        elseif($type == '3'){
        $models = Transaction::find()
                ->where(['in', 'to', $addrs])
                ->andWhere(['in' , 'tx_status', ['pending','success']])
                ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block')->orderBy('created_at desc');
        $models_2 = TransactionBtc::find()
                ->where(['in', 'to', $addrs])
                //->andWhere(['tx_status' => 'success'])
                ->andWhere(['in' , 'tx_status', ['pending','success']])
                ->select('tx_hash,coin_symbol,created_at,value_dec,from,to,tx_status,block')->orderBy('created_at desc');
        }

        $count = $models->count();
        $pages = new Pagination(['totalCount' =>$count, 'pageSize' =>$this->_pageSize]);
        $pages->setPage($page-1);
        $maxPage = $pages->getPageCount();
        $data = (new Query())->from(['tmpA' => $models->union($models_2)])->offset($pages->offset)->limit($pages->limit)->orderBy('created_at desc')->all();

        // p($data);exit();
        // 新增返回数据字段【货币图标：coin_icon,货币单位：coin_unit】
        $new_coin = Coins::find()->where(['symbol' => 'ETH'])->one();
        $new_coin_2 = Coins::find()->where(['symbol' => 'BTC'])->one();
        $new_coin_icon = $new_coin->icon ?? '';
        $new_coin_icon_2 = $new_coin_2->icon ?? '';
        if ($new_coin_icon) {
            $new_coin_icon = parent::get_user_avatar_url($new_coin_icon);
        }
        if ($new_coin_icon_2) {
            $new_coin_icon_2 = parent::get_user_avatar_url($new_coin_icon_2);
        }
        $new_coin_unit = $new_coin->unit ?? '';
        $new_coin_unit_2 = $new_coin_2->unit ?? '';

        // 获取当前块高度[BTC, ETH]
        $bitcoind = new BitcoinClient();
        $btc_req = $bitcoind->request('getblockcount',[]);
        if($btc_req['code'] == 0){
            $btc_block_number = 1355536;
        }else{
            $btc_block_number = $btc_req['data']->get();
        }

        $rpc_method = 'eth_blockNumber';
        $rpc_params = [];
        $rpc = new EthereumRPC($rpc_method, $rpc_params);

        $rpc_ret = $rpc->do_rpc();

        if($rpc_ret['code'] == 0){
            $eth_block_number = 5954635;
        }else{
            $eth_block_number = hexdec($rpc_ret['data']);
        }

        if(!empty($data)){
            foreach($data as $key => $item){
                $data[$key]['created_at'] = date('Y-m-d H:i:s', $item['created_at']);
                $rate = $coins[$item['coin_symbol']][$select];
                // 插入新增字段
                $data[$key]['coin_icon'] = $item['coin_symbol']==$new_coin['symbol'] ? $new_coin_icon : $new_coin_icon_2;
                $data[$key]['coin_unit'] = $item['coin_symbol']==$new_coin['symbol'] ? $new_coin_unit : $new_coin_unit_2;
                $data[$key]['confirmation_number'] = $item['coin_symbol']=='BTC' ? ($btc_block_number-$item['block']) : ($eth_block_number-$item['block']);

                if(in_array($item['from'], $addrs)){
                    $data[$key]['money'] = $s . sprintf('%.2f', $item['value_dec'] * $rate);
                    $data[$key]['value_dec'] = '-' . $item['value_dec'];
                
                }elseif(in_array($item['to'], $addrs)){
                    $data[$key]['money'] = $s . sprintf('%.2f', $item['value_dec'] * $rate);
                    $data[$key]['value_dec'] = '+' . $item['value_dec'];
                }
                // unset($data[$key]['from']);
                // unset($data[$key]['to']);
            }
            $ret = ['code' => 200, 'maxPage' => $maxPage, 'data' => $data, 'message' => 'success'];
            $this->do_aes(json_encode($ret));
        }else{
            $this->error_message('_No_Data_Query_');
        }
    }

    //查询版本类型
    public function actionGetVersion()
    {
        $request = yii::$app->request;
        $version = $request->post('os');
        $ver_num = $request->post('soft_ver');
        $android = Yii::$app->config->info('ANDROID_SOFT_VER');
        $ios = Yii::$app->config->info('IOS_SOFT_VER');
        $app_down_url = Yii::$app->config->info('APP_DOWNLOAD_URL');
        $descrp = Yii::$app->config->info('LOW_SOFT_VER');

        // if(!$version || !$downurl)
        if((!$version && $version == 0) || !$ver_num)
        {
            $this->error_message('参数不完整');
        }

        if ($version == 'android') {
            if(empty($ver_num)){
                $ret = array('code'=>501,'descrp'=>'信息不全');
                parent::do_aes(json_encode($ret));
            }
            if(!preg_match("/^\d+\.\d+\.\d+$/", $ver_num)){
                $ret = array('code'=>508,'descrp'=>'版本号格式不正确');
                parent::do_aes(json_encode($ret));
            }
            if(!empty($android) && $android > $ver_num){
                $ret = array('code'=>200,'data'=>$app_down_url,'descrp'=>$descrp);
                parent::do_aes(json_encode($ret));
            }else{
                $ret = array('code'=>500,'descrp'=>'当前已经是最新版本');
                parent::do_aes(json_encode($ret));
            }
        }elseif($version == 'ios'){
            if(empty($ver_num)){
                $ret = array('code'=>501,'descrp'=>'信息不全');
                parent::do_aes(json_encode($ret));
            }
            if(!preg_match("/^\d+\.\d+\.\d+$/", $ver_num)){
                $ret = array('code'=>508,'descrp'=>'版本号格式不正确');
                parent::do_aes(json_encode($ret));
            }
            $ver = Versions::find()->where(['type'=> 1,'status'=> 1])->max('ver_num');
            if(!empty($ios) && $ios > $ver_num){
                $ret = array('code'=>200,'data'=>$app_down_url,'descrp'=>$descrp);
                parent::do_aes(json_encode($ret));
            }else{
                $ret = array('code'=>500,'descrp'=>'当前已经是最新版本');
                parent::do_aes(json_encode($ret));
            }
        }else{
            $this->error_message('型号不存在');
        }
    }
    
    // 交易详情
    public function actionTransactionDetail(){
        // 验证token
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $uinfo = $this->checkToken($access_token);

        // 获取相关参数【交易hash：tx_hash, 货币标识：coin_symbol】
        $tx_hash = $request->post('tx_hash') ? $request->post('tx_hash') : '';
        $wallet_addr = $request->post('addr') ? $request->post('addr') : '';
        $coin_symbol = $request->post('coin_symbol') ? $request->post('coin_symbol') : '';
        if ($tx_hash=='' || $coin_symbol=='' || $wallet_addr=='') {
            $this->error_message('参数传递不正确！');
        }

        // 根据货币标识获取不同的数据模型[ETH, BTC],以及货币单位
        if ($coin_symbol == 'BTC') {
            $model = TransactionBtc::find()->where("tx_hash='".$tx_hash."'");
            $coin_unit = Coins::find()->where("symbol='".$coin_symbol."'")->one()->unit;
        }else{
            $model = Transaction::find()->where("tx_hash='".$tx_hash."'");
            $coin_unit = Coins::find()->where("symbol='".$coin_symbol."'")->one()->unit;
        }

        if (!$model->count()) {
            $this->error_message("没有该交易记录！");
        }
        
        // 获取当前块高度[BTC, ETH]
        switch ($coin_symbol) {
            case 'BTC':
                $bitcoind = new BitcoinClient();
                $btc_req = $bitcoind->request('getblockcount',[]);
                if($btc_req['code'] == 0){
                    // $block_number = 1355536;
                    return $this->error_message('获取参数(miner_money)失败！');
                }else{
                    $block_number = $btc_req['data']->get();
                }
                break;
            
            default:
                $rpc_method = 'eth_blockNumber';
                $rpc_params = [];
                $rpc = new EthereumRPC($rpc_method, $rpc_params);

                $rpc_ret = $rpc->do_rpc();

                if($rpc_ret['code'] == 0){
                    // $block_number = 5954635;
                    return $this->error_message('获取参数(miner_money)失败！');
                }else{
                    $block_number = hexdec($rpc_ret['data']);
                }
                break;
        }



        $model_data = $model->one();
        // 旷工费
        if ($coin_symbol == 'BTC') {
            $miner_money = $model_data->fee;
        }else{
            $miner_money = ((double)hexdec($model_data->gas_hex) * (double)hexdec($model_data->gas_price_hex)) / pow(10, 18);
        }
        
        // 确认数
        if($model_data->tx_status == 'success'){
            $confirmation_number = (double)$block_number - (double)$model_data->block;
        }else{
            $confirmation_number = 0;
        }




        // 判断当前用户是支出还是收入
        $balance_status = '';
        if ($wallet_addr == $model_data->from) {
            // 支出
            $balance_status = 'out';
        }else{
            // 收入
            $balance_status = 'in';
        }

        // 整理返回数据
        $result = [
            'tx_hash'             => $model_data->tx_hash,
            'status'              => $model_data->tx_status,// 交易状态
            'value_dec'           => $model_data->value_dec,// 交易金额
            'coin_unit'           => $coin_unit,// 货币单位
            'date'                => date('Y-m-d H:i:s', $model_data->updated_at),// 交易时间
            'miner_money'         => $miner_money,// 旷工费
            'confirmation_number' => $confirmation_number,// 确认数
            'output'              => $balance_status == 'out' ? $model_data->value_dec : '0',
            'input'               => $balance_status == 'in' ? $model_data->value_dec : '0',
            'input_data'          => $model_data->data,
            'from'                => $model_data->from,
            'to'                  => $model_data->to,
        ];
    
        $this->success_message($result);
    }
}

















