<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 16:56
 */

namespace api\controllers;

use Yii;
use common\jinglan\Jinglan;
use common\models\Article;
use jinglan\ves\VesRPC;
use jianyan\basics\common\models\sys\ArticleSingle;


class InitController extends ApibaseController
{
    public $modelClass = '';

    public function init(){
        parent::init();
    }

    public function actionInfo(){
        $about = array('關於我們'=>25,'IOS App'=>25,'Android App'=>25);
        $provision = array('使用條款'=>14,'隱私政策'=>13,'反洗錢條例'=>15,'費率說明'=>16,'站内公告'=>54); 
        $support = array('聯絡我們'=>17,'意見回饋'=>17,'Bug回報'=>17); 
    	$contact_us = array('Twitter'=>Yii::$app->config->info('WEB_LINK_TWITTER'),'Telegram'=>Yii::$app->config->info('WEB_LINK_TELEGRAM'),'FaceBook'=>Yii::$app->config->info('WEB_LINK_TELEGRAM'),'Medium'=>Yii::$app->config->info('WEB_LINK_TELEGRAM')); 
    	
        $url = Yii::$app->config->info('VIA_WEBSOCKET');
        $download_url = $this->actionGetHost().Yii::$app->config->info('APP_QRCODE_DOWNLOAD');
        $gonggao_url = Yii::$app->config->info('WEB_LINK_NOTICE');
        $site = array(
	        'title'=>Yii::$app->config->info('WEB_SITE_TITLE'),
	        'app_name'=>Yii::$app->config->info('WEB_APP_NAME'),
	        'logo'=>$this->actionGetHost().Yii::$app->config->info('WEB_SITE_LOGO'),
	        'logo_bottom'=>$this->actionGetHost().Yii::$app->config->info('WEB_SITE_LOGO_BOTTOM'),
	        'logo_app'=>$this->actionGetHost().Yii::$app->config->info('WEB_SITE_DOWNLOAD_LOGO'),
	        'description'=>Yii::$app->config->info('WEB_SITE_DESCRIPTION'),
	        'keyword'=>Yii::$app->config->info('WEB_SITE_KEYWORD'),
	        'copy_right'=>Yii::$app->config->info('WEB_COPYRIGHT_ALL'),
	        'icp'=>Yii::$app->config->info('WEB_SITE_ICP'),
	        'qrcode'=>Yii::$app->config->info('APP_QRCODE_DOWNLOAD'),
	        'download_url'=>Yii::$app->config->info('APP_DOWNLOAD_URL'),
	        'download_url_android'=>Yii::$app->config->info('APP_DOWNLOAD_ANDURL'),
	        'download_url_ios'=>Yii::$app->config->info('APP_DOWNLOAD_IOSURL'),
        ); 
        
        
        $ret = array(
            'about' => $about,
            'provision' => $provision,
            'support' => $support,
            'contact_us' => $contact_us,
            'via_websocket_url' => $url,
            'download_img_url' => $download_url,
            'gonggao_url' => $gonggao_url,
            'site' => $site,
            'usd_to_cny' => Jinglan::usd_to_cny(),
        );
        $this->success_message($ret);
    }
    
    public function actionInfof(){
    	$about = array('關於我們'=>25); 
    	$provision = array('使用條款'=>14,'隱私政策'=>13,'反洗錢條例'=>15,'費率說明'=>16); 
    	$support = array('上幣申請'=>17); 
    	$contact_us = array('Twitter'=>Yii::$app->config->info('WEB_LINK_TWITTER'),'Telegram'=>Yii::$app->config->info('WEB_LINK_TELEGRAM'),Yii::$app->config->info('WEB_EMAIL')=>'javascript:;'); 
    	
        $url = Yii::$app->config->info('VIA_WEBSOCKET');
        $download_url = $this->actionGetHost().Yii::$app->config->info('APP_QRCODE_DOWNLOAD');
        $gonggao_url = Yii::$app->config->info('WEB_LINK_NOTICE');
        $site = array(
	        'title'=>Yii::$app->config->info('WEB_SITE_TITLE'),
	        'app_name'=>Yii::$app->config->info('WEB_APP_NAME'),
	        'logo'=>$this->actionGetHost().Yii::$app->config->info('WEB_SITE_LOGO'),
	        'logo_bottom'=>$this->actionGetHost().Yii::$app->config->info('WEB_SITE_LOGO_BOTTOM'),
	        'logo_app'=>$this->actionGetHost().Yii::$app->config->info('WEB_SITE_DOWNLOAD_LOGO'),
	        'description'=>Yii::$app->config->info('WEB_SITE_DESCRIPTION'),
	        'keyword'=>Yii::$app->config->info('WEB_SITE_KEYWORD'),
	        'copy_right'=>Yii::$app->config->info('WEB_COPYRIGHT_ALL'),
	        'icp'=>Yii::$app->config->info('WEB_SITE_ICP'),
	        'qrcode'=>Yii::$app->config->info('APP_QRCODE_DOWNLOAD'),
	        'download_url'=>Yii::$app->config->info('APP_DOWNLOAD_URL'),
	        'download_url_android'=>Yii::$app->config->info('APP_DOWNLOAD_ANDURL'),
	        'download_url_ios'=>Yii::$app->config->info('APP_DOWNLOAD_IOSURL'),
        ); 
        
        
        $ret = array(
            'about' => $about,
            'provision' => $provision,
            'support' => $support,
            'contact_us' => $contact_us,
            'via_websocket_url' => $url,
            'download_img_url' => $download_url,
            'gonggao_url' => $gonggao_url,
            'site' => $site,
            'usd_to_cny' => Jinglan::usd_to_cny(),
        );
        $this->success_message($ret);
    }





    public function actionKline(){
        $m = $_REQUEST['symbol'];
        $from = $_REQUEST['from'];
        $to = $_REQUEST['to'];
        $interval = $_REQUEST['resolution'];
        $rpc_method = 'market.kline';
        if($interval=="1D"){
            $interval = 60*24;
        }
        if ($interval=="1W") {
             $interval = 60*24*7;
        }
        if ($interval=="1M") {
            $interval = 60*24*30;
        }
        $rpc = new VesRPC();
        $rpc_params = [$m, (int)$from, (int)$to, (int)$interval*60];
        $rpc_ret = $rpc->do_rpc($rpc_method, $rpc_params);
        if ($rpc_ret['code'] == 0) {
            die(json_encode($rpc_ret['data']));
        } else {
            $ret = $rpc_ret['data'];
        }
        $this->success_message($ret);
    }

    public function actionArticle(){
    	$all_title = ArticleSingle::find()->select('id,title')->where('status = 1')->asArray()->all();
    	
        $id = $_REQUEST['id'];
        $where['id'] = $id;
        $select = 'title,content,append,updated';
        $content = Article::find()->select($select)->where($where)->asArray()->one();
        if (empty($content)) {
                $content['title'] = '您要访问的内容不存在';
                $content['content'] = '您要访问的内容不存在';
                $content['append'] = 0;
                $content['updated'] = 0;
        }
        $content['all_title'] = $all_title;
        $this->success_message($content);
    }

    public function actionHelparticle(){
    	$all_title = ArticleSingle::find()->select('id,title')->where('status = 1')->asArray()->all();
    	
        $id = $_REQUEST['id'];
        $where['id'] = $id;
        $select = 'title,content,append,updated';
        $content = ArticleSingle::find()->select($select)->where($where)->asArray()->one();
        if (empty($content)) {
                $content['title'] = '您要访问的内容不存在';
                $content['content'] = '您要访问的内容不存在';
                $content['append'] = 0;
                $content['updated'] = 0;
        }
        $content['all_title'] = $all_title;
        $this->success_message($content);
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

}