<?php
namespace frontend\controllers;

use Yii;
use api\models\ExchangeCoins;
/**
 * Index controller
 */
class IndexController extends IController
{
    /**
     * 系统首页
     * @return string
     */
    public function actionIndex()
    {
        $header['title']= Yii::$app->config->info('WEB_SITE_TITLE') ;
        $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
        $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');
        $view = Yii::$app->view;
        $view->params['header']=$header;	  

        $exchange_coins_list = ExchangeCoins::find()->select('stock,money,decimals')->asArray()->all();
        if (!empty($exchange_coins_list)) {
          foreach ($exchange_coins_list as &$value) {
            $value['name'] = $value['stock'].$value['money'];
            unset($value['stock']);
            unset($value['money']);
          }
        }
        $exchange_coins_list = json_encode($exchange_coins_list);

        if ($this->isMobile3()) {
          return $this->redirect(['wap/download']);
        }else{
          return $this->render('index',["decimals" => $exchange_coins_list]); 
        }
    }

    function isMobile3(){
       if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array ('nokia', 'sony','ericsson','mot',
          'samsung','htc','sgh','lg','sharp',
          'sie-','philips','panasonic','alcatel',
          'lenovo','iphone','ipod','blackberry',
          'meizu','android','netfront','symbian',
          'ucweb','windowsce','palm','operamini',
          'operamobi','openwave','nexusone','cldc',
          'midp','wap','mobile'
          );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    }



}
