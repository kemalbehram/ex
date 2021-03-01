<?php
/**
 * Created by PhpStorm.
 * User: op
 * Date: 2018-05-30
 * Time: 19:45
 */

namespace api\controllers;

use Yii;
use common\jinglan\Trade;
use common\jinglan\CreateWallet;
use api\models\ExchangeCoins;
use api\models\Coin;

class ExchangeController extends ApibaseController
{
    public $modelClass = '';

    public function init(){
        parent::init();
    }

    public function actionMarket()
    {
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        if (!empty($access_token)){
            $uinfo = $os == 'web' ? $this->memberToken($access_token) : $this->checkToken($access_token);
            $uid = $uinfo['id'];
        }else{
            $uid = 0;
        }
        Trade::market_v3($uid);
    }

    public function actionMarketRecommend()
    {
        $request = Yii::$app->request;
        $platform = $request->post('os');
        $limit = $platform === 'pc' ? 8 : 6;
        $coins = Coin::find()->where(['enable' => 1])->select('symbol,icon,unit,ram_token_decimals,usd,cny')->orderBy('listorder DESC')->asArray()->all();
        $coins = array_column($coins, NULL, 'symbol');
        $coin_keys = array_keys($coins);

        $market = ExchangeCoins::find()->where(['enable' => 1])->andWhere(['in', 'stock', $coin_keys])->andWhere(['in', 'money', $coin_keys])->select('stock,money,limit_amount as min_amount,taker_fee,maker_fee')->orderBy('recommend DESC,listorder DESC')->limit($limit)->asArray()->all();

        $this->success_message($market);
    }

    public function actionMarketChuangxin()
    {
        $coins = Coin::find()->where(['enable' => 1])->select('symbol,icon,unit,ram_token_decimals,usd,cny')->orderBy('listorder DESC')->asArray()->all();
        $coins = array_column($coins, NULL, 'symbol');
        $coin_keys = array_keys($coins);

        $market = ExchangeCoins::find()->where(['enable' => 1])->where(['is_chuangxin' => 1])->andWhere(['in', 'stock', $coin_keys])->andWhere(['in', 'money', $coin_keys])->select('stock,money,limit_amount as min_amount,taker_fee,maker_fee')->orderBy('recommend DESC,listorder DESC')->limit(8)->asArray()->all();

        $this->success_message($market);
    }


    public function actionBalance()
    {
        $request = Yii::$app->request;

        $access_token = $request->post('access_token');
        if (!empty($access_token)){
            $uinfo = $this->checkToken($access_token);
            $uid = $uinfo['id'];
        }else{
            $uid = 0;
        }
        Trade::balance_v2($uid);
        //Trade::balance($uid);
    }

    //Exchange-CC-04.交易所限价单买卖
    public function actionOrderLimit(){
        $request = Yii::$app->request;

        $access_token = $request->post('access_token');
        $uinfo = $this->checkToken($access_token);
        $uid = $uinfo['id'];

        Trade::order($uid);
    }

    // 撤销委托单
    public function actionCancelOrder()
    {
        $request = Yii::$app->request;

        $access_token = $request->post('access_token');
        $uinfo = $this->checkToken($access_token);
        $uid = $uinfo['id'];

        Trade::cancelOrder($uid);
    }

    // 生成货币地址
    public function actionGenerateAddress()
    {
        $request = Yii::$app->request;

        $access_token = $request->post('access_token');
        $uinfo = $this->checkToken($access_token);
        $uid = $uinfo['id'];

        CreateWallet::create_v2($uid);
    }
}