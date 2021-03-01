<?php
namespace frontend\controllers;

use Yii;
use api\models\ExchangeCoins;

/**
 * Index controller
 */
class TradeController extends IController
{

    /**
     * 系统首页
     * @return string
     */
    public function actionIndex()
    {
	    $request = Yii::$app->request;
        $stock = $request->get('stock');
        $money = $request->get('money');
        $header['title']= $stock."/".$money ." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	    $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	    $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
        $view = Yii::$app->view;
	    $view->params['header']=$header;

        $where = "stock = '".$stock."' and money = '".$money."'";
        $exchange_coins_list = ExchangeCoins::find()->where($where)->asArray()->one();
        if (empty($exchange_coins_list["decimals"])) {
            $decimal_length = 6;
        }else{
            $decimal_length = $exchange_coins_list["decimals"];
        }

        return $this->render('index',["stock" =>$stock,"money" =>$money,"decimal_length" =>$decimal_length]);die('222');
    }
}
