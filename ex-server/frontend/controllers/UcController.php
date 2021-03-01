<?php
namespace frontend\controllers;

use Yii;
use yii\web\Session;
use api\models\ExchangeCoins;
use jinglan\ves\VesRPC;
use common\servers\DeviceDetect;
use common\models\MemberWealthOrder;
use common\models\MemberWealthPackage;

/**
 * Index controller
 */

class UcController extends IController
{

	public function __construct($id, $module, $config = []){
		$session = new Session;
		$session->open();
    	if(intval($session['user'])<=0){
    		$this->redirect('/login');
    	}
    	parent::__construct($id, $module, $config);		
	}
    //个人中心
    public function actionIndex()
    {

          $header['title']= "用户中心"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	      	
        return $this->render('index');
    }
    
    //充值
    public function actionRecharge()
    {
           $header['title']= "充值"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	     	
        return $this->render('recharge');
    }
    
    //提现
    public function actionWithdraw()
    {
          $header['title']=  "提现"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	      	
        return $this->render('withdraw');
    }
    
    //我的资产
    public function actionAssets()
    {
          $header['title']=  "我的资产"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	      	
        return $this->render('assets');
    }
  
  	//挖矿记录
    public function actionMining()
    {
        $header['title']=  "我的资产"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
        $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
        $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
        $view = Yii::$app->view;
        $view->params['header']=$header;  
          // 获取筛选条件参数
        $request = Yii::$app->request;
        $market = $request->get('market','');
        if (empty($market)){
            $market = ExchangeCoins::getMarketName();
            $market = $market[0];
        }
        $last_id = $request->get('last_id') ?: 1;
        $limit   = $request->get('limit') ?: 10;
        // 返回参数
        $code = 0;
        $msg  = null;
        $data = null;

        $rpc = new VesRPC();
        $map1 = 'market.deals';
        $map2 = [$market, (int)$limit, (int)$last_id];
        $rpc_ret = $rpc->do_rpc($map1, $map2);
        if ($rpc_ret['code'] == 0) {
            $msg  = $rpc_ret['data'];
        }else{
            $code = 1;
            $data = $rpc_ret['data'];
        }

        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
            'market' => $market,
            'limit' => $limit,
        ];
        return $this->render('mining',$result);
    }
    
    //财务日志
    public function actionCashlog()
    {
          $header['title']= "充提历史"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	      	
        return $this->render('cashlog');
    }
    
    //委托管理
    public function actionEntrusted()
    {
           $header['title']= "委托管理"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	     	
        return $this->render('entrusted');
    }
    
    //我的成交
    public function actionClinch()
    {
           $header['title']="我的成交"." - ". Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	     	
        return $this->render('clinch');
    }
    
    //登录密码
    public function actionPassword()
    {
          $header['title']= "修改登陆密码"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	      	
        return $this->render('password');
    }
    
    //实名认证
    public function actionVerified()
    {
          $header['title']= "实名认证"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	  	
        return $this->render('verified');
    }
   
    //绑定手机
    public function actionBindphone()
    {
          $header['title']= "绑定手机"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
    $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
    $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
    $view->params['header']=$header;            
        return $this->render('bindphone');
    }
    

    //实名邮箱
    public function actionBindemail()
    {
        $header['title']= "绑定邮箱"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
        $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
        $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
        $view = Yii::$app->view;
        $view->params['header']=$header;            
        return $this->render('bindemail');
    }
     
    //系统消息
    public function actionMessage()
    {
          $header['title']= "系统消息"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
	  $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
	  $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
         $view = Yii::$app->view;
	  $view->params['header']=$header;	    	
        return $this->render('message');
    }

    //商家认证
    public function actionBussiness()
    {
         $header['title']= Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');       
             $view = Yii::$app->view;
          $view->params['header']=$header;
        $detect = new DeviceDetect;
        if ($detect->isMobile()){
           $this->layout='@app/views/layouts/main-mb.php';  
            return $this->render('bussiness', [
                
            ]);
        }else{
            return $this->render('bussiness', [
                
            ]);
        }

    }
    //商家认证审核状态
    public function actionFillout()
    {
         $header['title']= Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');       
             $view = Yii::$app->view;
          $view->params['header']=$header;
        $detect = new DeviceDetect;
        if ($detect->isMobile()){
           $this->layout='@app/views/layouts/main-mb.php';  
            return $this->render('fillout', [
                
            ]);
        }else{
            return $this->render('fillout', [
                
            ]);
        }

    }

   //邀请好友
    public function actionInviteFriends()
    {
          $header['title']= "邀请好友"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
          $view = Yii::$app->view;
          $view->params['header']=$header;            
        return $this->render('invite');
    }
 
   //矿机理财
    public function actionWealth()
    {
          $header['title']= "邀请好友"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
          $view = Yii::$app->view;
          $view->params['header']=$header;            
        return $this->render('wealth');
    }

   //锁仓管理
    public function actionWealthlock()
    {
          $header['title']= "锁仓管理"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
          $view = Yii::$app->view;
          $view->params['header']=$header;            
        return $this->render('wealthlock');
    }
 
    //锁仓管理2
    public function actionWealthlock2()
    {
          $header['title']= "锁仓管理"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
          $view = Yii::$app->view;
          $view->params['header']=$header;            
        return $this->render('wealthlock2');
    }
 

   //矿机理财
    public function actionWealthbuy()
    {
        $header['title']= "邀请好友"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
        $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
        $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
        $view = Yii::$app->view;
        $view->params['header']=$header;

        $request  = Yii::$app->request;
        $id       = $request->get('id');
        if(empty($id)){
          die("该商品已经售完");
        }
        $data = (new \yii\db\Query())->from('jl_member_wealth_package')->where(['status' => 1,'id'=>$id])->one();
        if(empty($data)){
          die("该商品已经售完");
        }
//var_dump($data);die();

        $data['min_num'] = sprintf('%.2f',$data['min_num']);

        return $this->render('wealthbuy', [
                'wealtdetail'=>$data,
        ]);
    }


    //邀请好友
    public function actionApi()
    {
          $header['title']= "API管理"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
          $view = Yii::$app->view;
          $view->params['header']=$header;            
          return $this->render('api');
    }   
    
    
    //邀请好友
    public function actionAgent()
    {
          $header['title']= "代理管理"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
          $view = Yii::$app->view;
          $view->params['header']=$header;            
          return $this->render('agent');
    }


    //机器人
    public function actionRobot()
    {
          $header['title']= "机器人"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
          $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
          $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
          $view = Yii::$app->view;
          $view->params['header']=$header; 

          return $this->render('robot',[
                'ticker'=>"",
        ] );
    }

}
