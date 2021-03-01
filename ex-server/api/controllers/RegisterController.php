<?php

namespace api\controllers;

use common\jinglan\DSActivity;
use common\jinglan\Common;
use common\jinglan\Jinglan;
use common\jinglan\Reward;
use Yii;
use api\models\Varcode;
use api\models\IpLog;
use api\models\Member;
use jinglan\sms\SMS;
use common\models\Coins;

use common\models\base\AccessToken;
use common\helpers\StringHelper;
use yii\web\UploadedFile;
use common\helpers\FileHelper;
use yii\swiftmailer\Mailer;
use api\models\GoogleAuthenticator;
use api\models\EmailCode;
use api\models\CountryCode;
use yii\web\Session;


class RegisterController extends ApibaseController
{
    public $modelClass = '';

    public function init(){
        parent::init();
    }

    //获取手机验证码
    public function actionMobileVarcode(){

        $request = Yii::$app->request;
        $mobile_area_code = $request->post('area_code');
        $mobile_phone = $request->post('mobile_phone');
        if(strlen($mobile_phone)>1){
            $first_letter = substr($mobile_phone, 0,1);
            //var_dump($first_letter);
            if($first_letter=='0'){
                $mobile_phone = substr($mobile_phone, 1);
                //var_dump($mobile_phone);
            }
            //die();
        }
        $type = $request->post('type');
        $this->check_empty($type,'_NOT_Empty_');

        $qian=array(" ","　","\t","\n","\r");
        $mobile_area_code = str_replace($qian, '', $mobile_area_code);

        if (empty($mobile_area_code) || $mobile_area_code == '+886') {
            $mobile_area_code = '886';
            $mobile_phone = Jinglan::check_mobile_phone($mobile_phone);
        }else{
            $mobile_area_code = str_replace("+","",$mobile_area_code);
        }


        if ($type === 3) {
          $var = Member::find()->where(['username'=>$mobile_phone])->one();
        } else {
          $var = Member::find()->where(['username'=>$mobile_area_code.$mobile_phone])->one();
        }

        if(!empty($var)&&$type==1){
            $this->error_message('_MobilePhone_Exist_');
        }

        if(empty($var)&&$type==2){
          $this->error_message('_Please_first_register_');
        }

        //兼容提现
        if ($type == 3) {
            $mobile_area_code = '';
            if(substr($mobile_phone, 0, 2) == '86'){
                $mobile_area_code = '86';
                $mobile_phone = substr($mobile_phone,2);
            }
        }

        Common::send_mob_varcode($mobile_phone,$mobile_area_code);

    }
  
        //获取邮箱验证码
    public function actionEmailVarcode(){
        $request = Yii::$app->request;
        $email = trim($request->post('email'));
        $type = $request->post('type');
        $this->check_empty($email,'_NOT_Empty_');
        $this->check_empty($type,'_NOT_Empty_'); 
        $this->doSendEmail($email,$type);
    }

    protected function  doSendEmail($email,$type){
        $email = Jinglan::check_email($email);
        $var = Member::find()->where(['email'=>$email])->one();
        if(!empty($var)&&$type==1){
            $this->error_message('_Email_Exist_');
        }
        if(empty($var)&&$type==2){
            $this->error_message('_Please_first_register_');
        }
        
       $email_data= EmailCode::find()->where( ['email'=>$email] )->one();

       $limit_time = Yii::$app->config->info("SMS_SEND_LIMIT_TIME") > 0 ? intval(Yii::$app->config->info("SMS_SEND_LIMIT_TIME")) : 5;
        if(   $email_data ){
            if((time() - $email_data->updated_at)< $limit_time*60){
                $this->error_message('每'.$limit_time.'分钟内限发一次');
            }
        }
         $vcode = mt_rand(100000,999999);
         $mail= Yii::$app->mailer->compose();   
         $mail->setTo( $email);  
         $mail->setSubject(Yii::$app->config->info('EMAIL_VERIFICATION_SUBJECT'));  
         $content = Yii::$app->config->info('EMAIL_VERIFICATION_CONTENT');
         $content = str_replace("#code#", $vcode,$content);
         $mail->setHtmlBody($content);    //发布可以带html标签的文本         
        if($mail->send())  {//if($mail->send())
            //邮件发送成功后 向邮件验证码表中添加一条数据 , 应在上面按照时间判断，时间在一分钟之内，不许再次发送，时间超过一分钟，可发送，一个账户之内使用一条数据
          if(   $email_data ){// $email_data 
            
             $email_data->varcode=  $vcode ;
             $email_data->type=   $type ;
               if( $type = 2){
               $email_data->user_id = $var['id'] ;
            }
            if($email_data->save() > 0){
               //$this->success_message(['vcode1'=>$vcode]);
               $this->success_message();
            }else{
               $this->error_message('_Please_first_register_');
            }
          }else{
             $email_model= new EmailCode();
             $email_model->email=  $email ;
             $email_model->varcode=  $vcode ;
             $email_model->type=   $type ;
             if( $type = 2){
               $email_model->user_id = $var['id'] ;
            }
        
            if($email_model->save() > 0){
               //$this->success_message(['vcode2'=>$vcode]);
                $this->success_message();
            }else{
               $this->error_message('_Please_first_register_');
            }
           }
       }else{
         $this->error_message('_Please_first_register_');
       }    
    }
    protected function checkSecond($uid){
        $member_model = Member::find()->where(['id'=>$uid])->one();
        if(!empty($member_model)){
            if($member_model->two_step_open_status == 1 && $member_model->last_time < 0){
                $ret = array('code'=>521,'message'=>"為了您的賬戶安全,請進行二次驗證");
                $check_way = array();
                if(!empty($member_model->email)){
                    $item = array();
                    $item['type'] = 'email';
                    $item['name'] = '信箱驗證';
                    $s = explode("@", $member_model->email);
                    if(count($s)>1){
                        $prefix = $s[0];
                        if(strlen($s[0]>3)){
                            $prefix = substr($prefix,0,3)."***";
                        }elseif(strlen($s[0]>2)){
                            $prefix = substr($prefix,0,2)."***";
                        }elseif(strlen($s[0]>1)){
                            $prefix = substr($prefix,0,1)."***";
                        }
                        $item['account'] = $prefix."@".$s[1];  
                    }else{
                        $item['account'] = $member_model->email;  
                    }
                    $check_way[] = $item;
                }
                if(!empty($member_model->mobile_phone)){
                    $item = array();
                    $item['type'] = 'mobile';
                    $item['name'] = '手機驗證碼';
                    if(strlen($member_model->mobile_phone)>8){
                        $phone = $member_model->mobile_phone;
                        $item['account'] = substr($phone,0,4)."***".substr($phone,-4);
                    }else{
                        $item['account'] = $member_model->mobile_phone;  
                    }                    
                    $check_way[] = $item;  
                }
                if($member_model->is_google_check == 1){
                    $item = array();
                    $item['type'] = 'google';
                    $item['name'] = '谷歌驗證器';
                    $item['account'] = "$uid"; 
                    $check_way[] = $item; 
                }
                $ret['check_way'] = $check_way;

                $temp_token = Yii::$app->security->generateRandomString();
                $member_model->temp_token = $temp_token ;

                if($member_model->save()>0){
                    $ret['temp_token'] = $temp_token ;
                    die(json_encode($ret));                                  
                }else{
                    $this->error_message('登錄操作異常');
                }
            }
        }
    }

    public function actionSecondCode(){
        $request = Yii::$app->request;
        $check_way = $request->post('check_way');
        $this->check_empty($check_way,'請選擇驗證方式');
        $temp_token = $request->post('temp_token');
        $this->check_empty($temp_token,'缺少臨時登錄憑證');
        $member_model = Member::find()->where(['temp_token'=>$temp_token])->one();
        if(!empty($member_model)){
            if($check_way=='email'){
                if(empty($member_model->email)){
                    $this->error_message('Email不存在');
                }else{
                    $this->doSendEmail($member_model->email,2);
                }
            }elseif($check_way=='mobile'){
                if(empty($member_model->mobile_phone)){
                    $this->error_message('Mobile不存在');
                }else{
                    $mobile_area_code = substr($member_model->mobile_phone, 0, 2);
                    $mobile_phone = substr($member_model->mobile_phone,2);
                    Common::send_mob_varcode($mobile_phone,$mobile_area_code);                    
                }
            }else{
                $this->error_message('參數無效');
            }
        }else{
            $this->error_message('未找到用戶信息');
        }
    }

    protected function directLogin($uid){
        $request = Yii::$app->request;
        $member_model = Member::find()->where(['id'=>$uid])->one();
        $member_model->last_time = time();
        $member_model->last_ip = Yii::$app->request->getUserIP();
        $os = strtolower($request->post('os'));
        if (in_array($os, ['ios','android'])){

        }else{
            // $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
        }

        if ($member_model->save(false)){
            if (in_array($os, ['ios','android'])){
                $user_id = $member_model->attributes['id'];

                $access_token = AccessToken::getMemberTokenInfoByUid($user_id)['access_token'];
                if (empty($access_token)){
                    $group = 1;
                    $rst = AccessToken::setMemberInfo($group, $user_id);
                    $access_token = $rst['access_token'];
                }
                $from = $os;
            }else{
                $session = new Session;
                $session->open();
                $session["user"]= $member_model['id'];
                $session["email"]= $member_model['email'];
                $session["mobile_phone"]= $member_model['mobile_phone'];
                $session["access_token"]= $member_model->access_token;
                $access_token = $member_model->access_token;
                $from = 'web';
            }

            $this->success_message(['access_token'=>$access_token,'from'=>$from , 'is_google_check' => $member_model['is_google_check'] ]);
        }else{
            $this->error_message('_Try_Again_Later_');
        }        
    }

    public function actionSecondLogin(){
        $request = Yii::$app->request;
        $check_way = $request->post('check_way');
        $this->check_empty($check_way,'請選擇驗證方式');
        $temp_token = $request->post('temp_token');
        $this->check_empty($temp_token,'缺少臨時登錄憑證');
        $code = $request->post('code');
        $this->check_empty($temp_token,'請輸入驗證碼');        
        $member_model = Member::find()->where(['temp_token'=>$temp_token])->one();
        if(!empty($member_model)){
            if($check_way=='email'){
                if(empty($member_model->email)){
                    $this->error_message('Email不存在');
                }else{
                     $varcode_result = EmailCode::find()->where(['email'=>$member_model->email, 'varcode'=>$code])->one();
                     if($varcode_result){
                        $this->directLogin($member_model->id);
                    }else{
                       $this->error_message('驗證失敗');
                    }
                }                
            }elseif($check_way=='mobile'){
                if(empty($member_model->mobile_phone)){
                    $this->error_message('Mobile不存在');
                }else{
                     $varcode_result = Varcode::find()->where(['mobile_phone' =>$member_model->mobile_phone, 'varcode'=>$code])->one();
                     if($varcode_result){
                        $this->directLogin($member_model->id);
                    }else{
                       $this->error_message('驗證失敗');
                    }
                }
            }elseif($check_way=='google'){
                if($member_model->is_google_check==0){
                    $this->error_message('未開啟谷歌驗證');
                }else{
                    $g_auth = new GoogleAuthenticator();
                    $vcode  = $g_auth->getCode($member_model->google_check_key);
                    if($vcode == $code){
                        $this->directLogin($member_model->id);
                    }else{
                        $this->error_message('驗證失敗');
                    }
                }                

            }else{
                $this->error_message('參數無效');
            }
        }else{
            $this->error_message('未找到用戶信息');
        }
    }

    //登录
    public function actionSign(){
        $request = Yii::$app->request;
        $mobile_phone = $request->post('mobile_phone');
        $mobile_phone = Jinglan::check_mobile_phone($mobile_phone);

        $password = $request->post('password');
        $this->check_empty($password,'_Password_Cannot_Be_empty_');
        if(strlen($password)<6){
            $this->error_message('_Enter_at_least_6_digits_of_the_password');
        }

        // $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
        $member_model = Member::find()->where(['username'=>$mobile_phone])->one();

        if(!empty($member_model)){
            /************新增禁用用户************/
            if ($member_model->status == 0) {
                $this->error_message('_The_user_has_been_locked_and_temporarily_unable_to_land_');
            }
            /**********结束新增禁用用户**********/


            $ha =  $member_model['password_hash'];
            if(empty($ha)){
                $this->error_message('暂无密码');
            }
            // p($ha);exit;
            $model = Yii::$app->getSecurity()->validatePassword($password, $ha);
            if($model == 1){

                /***二次验证****/
                $this->checkSecond($member_model->id);

                $member_model->last_time = time();
                $member_model->last_ip = Yii::$app->request->getUserIP();
                $os = strtolower($request->post('os'));
                if (in_array($os, ['ios','android'])){
                     $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
                }else{
                     $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
                }

                if ($member_model->save(false)){
                    if (in_array($os, ['ios','android'])){
                        $user_id = $member_model->attributes['id'];

                        $access_token = AccessToken::getMemberTokenInfoByUid($user_id)['access_token'];
                        if (empty($access_token)){
                            $group = 1;
                            $rst = AccessToken::setMemberInfo($group, $user_id);
                            $access_token = $rst['access_token'];
                        }
                        $from = $os;
                    }else{
                        $session = new Session;
                        $session->open();
                        $session["user"]= $member_model['id'];
                        $session["email"]= $member_model['email'];
                        $session["mobile_phone"]= $member_model['mobile_phone'];
                        $session["access_token"]= $member_model->access_token;
                        $access_token = $member_model->access_token;
                        $from = 'web';
                    }

                    $this->success_message(['access_token'=>$access_token,'from'=>$from , 'is_google_check' => $member_model['is_google_check'] ]);
                }else{
                    $this->error_message('_Try_Again_Later_');
                }
            }else{
                $this->error_message('_The_Password_Error_');
            }
        }else{
            $this->error_message('_Please_first_register_');
        }
    }
        
        //邮箱登录
    public function actionEmailSign(){
        $request = Yii::$app->request;
         $email = $request->post('email');
         $email= Jinglan::check_email($email);
     
        $password = $request->post('password');
        $this->check_empty($password,'_Password_Cannot_Be_empty_');
        if(strlen($password)<6){
            $this->error_message('_Enter_at_least_6_digits_of_the_password');
        }

        // $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
        $member_model = Member::find()->where(['email'=>$email])->one();

        if(!empty($member_model)){
            /************新增禁用用户************/
            if ($member_model->status == 0) {
                $this->error_message('_The_user_has_been_locked_and_temporarily_unable_to_land_');
            }
            /**********结束新增禁用用户**********/
            $ha =  $member_model['password_hash'];
            if(empty($ha)){
                $this->error_message('暂无密码');
            }
            // p($ha);exit;
            $model = Yii::$app->getSecurity()->validatePassword($password, $ha);
            if($model == 1){


                /***二次验证****/
                $this->checkSecond($member_model->id);


                $member_model->last_time = time();
                $member_model->last_ip = Yii::$app->request->getUserIP();
                $os = strtolower($request->post('os'));
                if (in_array($os, ['ios','android'])){

                }else{
                    // $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
                }

                if ($member_model->save(false)){
                    if (in_array($os, ['ios','android'])){
                        $user_id = $member_model->attributes['id'];

                        $access_token = AccessToken::getMemberTokenInfoByUid($user_id)['access_token'];
                        if (empty($access_token)){
                            $group = 1;
                            $rst = AccessToken::setMemberInfo($group, $user_id);
                            $access_token = $rst['access_token'];
                        }
                        $from = $os;
                    }else{
                        $session = new Session;
                        $session->open();
                        $session["user"]= $member_model['id'];
                        $session["email"]= $member_model['email'];
                        $session["mobile_phone"]= $member_model['mobile_phone'];
                        $session["access_token"]= $member_model->access_token;
                        $access_token = $member_model->access_token;
                        $from = 'web';
                    }

                   $this->success_message(['access_token'=>$access_token,'from'=>$from ]);
                }else{
                    $this->error_message('_Try_Again_Later_');
                }
            }else{
                $this->error_message('_The_Password_Error_');
            }
        }else{
            $this->error_message('_Please_first_register_');
        }
    }

    //注册
    public function actionRegister(){
        $request = Yii::$app->request;
        $mobile_phone = $request->post('mobile_phone');
        $code = $request->post('code');
        $mobile_area_code = $request->post('area_code');


        $qian=array(" ","　","\t","\n","\r");
        $mobile_area_code = str_replace($qian, '', $mobile_area_code); 

        if (empty($mobile_area_code) || $mobile_area_code == '+86') {
            $mobile_area_code = '86';
            $mobile_phone = Jinglan::check_mobile_phone($mobile_phone);
        }else{
            $mobile_area_code = str_replace("+","",$mobile_area_code);
        }
        $mobile_phone = Jinglan::check_mobile_phone($mobile_area_code.$mobile_phone);

        $varcode = $request->post('varcode');
        $this->check_empty($varcode,'_The_Verification_Code_Can_Not_Be_Empty_');
        $password = $request->post('password');
        $this->check_empty($password,'_Password_Cannot_Be_empty_');
        if(strlen($password)<6){
            $this->error_message('_Enter_at_least_6_digits_of_the_password');
        }
        if(!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/',$password)){
            $this->error_message('必须同时包含字母和数字');
        }
        $repassword = $request->post('repassword');
        $this->check_empty($repassword,'_Confirm_Password_Must_Not_Be_Empty_');
        if($password  !== $repassword){
            $this->error_message('_The_Two_Password_Input_Is_Inconsistent_');
        }


        $member = Member::find()->where(['mobile_phone'=>$mobile_phone,'mobile_phone_status'=>1])->one();
        if(!empty($member)){
            $this->error_message('_MobilePhone_Exist_');
        }
        if(!empty($code)){
            $c = Member::find()->where(['code'=>$code])->one();
            if(empty($c)){
                $this->error_message('邀请码无效!');
            }
            $varcode_result = Varcode::find()->where(['mobile_phone' => $mobile_phone, 'varcode'=>$varcode])->one();
            //$varcode_result = 1;
            if (!empty($varcode_result)){//验证成功
                $password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
                $member_model= new Member();
                $member_model->username = $mobile_phone;
                $member_model->mobile_phone = $mobile_phone;
                $member_model->mobile_phone_status = 1;
                $member_model->password_hash = $password_hash;
                $member_model->code = StringHelper::random(6);
                if(empty($c['path'])){
                    $member_model->path = $c['id'];
                }else{
                    $member_model->path = $c['id'] .'-'.$c['path'];
                }
                $member_model->last_member = $c['id'];
                $member_model->nickname = StringHelper::random(8);
                $member_model->head_portrait = '/attachment/images/head_portrait.png';
                $member_model->created_at = time();
                $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
                $member_model->visit_count = 1;
                $member_model->last_time = 0;
                $member_model->last_ip = Yii::$app->request->getUserIP();


                $reward_status = Yii::$app->config->info('INVITE_REG_REWARD_STATUS');
                $reward = Yii::$app->config->info('INVITE_REG_REWARD');

                $reward_coin = Yii::$app->config->info('PLATFORM_COIN_SYMBOL');
                $usd_price = Reward::coin_usd_price($reward_coin);
                if($usd_price>0){
                    $reward = $reward * $usd_price;
                }else{
                    $reward = 0;                    
                }

                if($reward_status == 1){
                    $member_model->invite_rewards = $reward;
                    $member_model->freeze_rewards  =  $reward;
                    $member_model->total_invite_rewards  =  $reward;
                }
                if($member_model->save() > 0){
                    $os = strtolower($request->post('os'));
                    if (in_array($os, ['ios','android','pc'])){
                        $user_id = $member_model->attributes['id'];
                        $group = 1;
                        $rst = AccessToken::setMemberInfo($group, $user_id);
                        $access_token = $rst['access_token'];
                        $from = $os;
                    }else{
                        $user_id = $member_model->attributes['id'];
                        $access_token = $member_model['access_token'];
                        $from = 'web';
                    }
                    $c['son_member'] = $c['son_member']+1;
                    $c->save();
                    $this->parentReward($code);
                    DSActivity::regReward($user_id);
                    $this->success_message(['access_token'=>$access_token,'from'=>$from]);
                }else{
                    $this->error_message('_Registration_Failed_Try_Later_');
                }
            }else{
                $this->error_message('_VerCode_Error_');
            }
        }else{
            $varcode_result = Varcode::find()->where(['mobile_phone' => $mobile_phone, 'varcode'=>$varcode])->one();
            if (!empty($varcode_result)){//验证成功
                $password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
                $member_model= new Member();
                $member_model->username = $mobile_phone;
                $member_model->mobile_phone = $mobile_phone;
                $member_model->mobile_phone_status = 1;
                $member_model->password_hash = $password_hash;
                $member_model->code = StringHelper::random(6);
                $member_model->nickname = StringHelper::random(8);
                $member_model->head_portrait = '/attachment/images/head_portrait.png';
                $member_model->created_at = time();
                $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
                $member_model->visit_count = 1;
                $member_model->last_time = time();
                $member_model->last_ip = Yii::$app->request->getUserIP();

                if($member_model->save() > 0){
                    $os = strtolower($request->post('os'));
                    if (in_array($os, ['ios','android','pc'])){
                        $user_id = $member_model->attributes['id'];
                        $group = 1;
                        $rst = AccessToken::setMemberInfo($group, $user_id);
                        $access_token = $rst['access_token'];
                        $from = $os;
                    }else{
                        $user_id = $member_model->attributes['id'];                        
                        $access_token = $member_model['access_token'];
                        $from = 'web';
                    }
                    DSActivity::regReward($user_id);
                    $this->success_message(['access_token'=>$access_token,'from'=>$from]);
                }else{
                    // $errors = $member_model->getErrors();
                    // var_dump($errors);die();
                    $this->error_message('_Registration_Failed_Try_Later_');
                }
            }else{
                $this->error_message('_VerCode_Error_');
            }
        }
    }
    //上级奖励
    private function parentReward($code){

        $reward_coin = Yii::$app->config->info('PLATFORM_COIN_SYMBOL');
        $status = Yii::$app->config->info('INVITE_REG_REWARD_STATUS');
        $level1_reward = Yii::$app->config->info('INVITE_REG_LEVEL1_REWARD');
        $level2_reward = Yii::$app->config->info('INVITE_REG_LEVEL2_REWARD');
        $level3_reward = Yii::$app->config->info('INVITE_REG_LEVEL3_REWARD');

        $usd_price = Reward::coin_usd_price($reward_coin);
        if($usd_price>0){
            $level1_reward = $level1_reward / $usd_price;
            $level2_reward = $level2_reward / $usd_price;
            $level3_reward = $level3_reward / $usd_price;
        }else{
            $level1_reward = 0;  
            $level2_reward = 0;
            $level3_reward = 0;                  
        }

        //1代
        $parent_user = Member::find()->where(['code'=>$code])->one();
        if(!empty($parent_user)){
            $parent_user['son_1_num'] = $parent_user['son_1_num']+1;
            if($status == 1){
                $parent_user['invite_rewards'] =  $parent_user['invite_rewards']+ $level1_reward;
                $parent_user['freeze_rewards'] =  $parent_user['freeze_rewards']+ $level1_reward;
                $parent_user['total_invite_rewards'] = $parent_user['total_invite_rewards']+ $level1_reward;
            }
            $parent_user->save();       
            //2代
            if(!empty($parent_user['last_member'])){
                $parent_user2 = Member::find()->where(['id'=>$parent_user['last_member']])->one();
                if(!empty($parent_user2)){
                    $parent_user2['son_2_num'] = $parent_user2['son_2_num']+1;
                    if($status == 1){
                        $parent_user2['invite_rewards'] =  $parent_user2['invite_rewards']+ $level2_reward;
                        $parent_user2['freeze_rewards'] =  $parent_user2['freeze_rewards']+ $level2_reward;
                        $parent_user2['total_invite_rewards'] = $parent_user2['total_invite_rewards']+ $level2_reward;
                    }
                    $parent_user2->save();  

                    //3代
                    if(!empty($parent_user2['last_member'])){
                        $parent_user3 = Member::find()->where(['id'=>$parent_user2['last_member']])->one();
                        if(!empty($parent_user3)){
                            $parent_user3['son_3_num'] = $parent_user3['son_3_num']+1;
                            if($status == 1){
                                $parent_user3['invite_rewards'] =  $parent_user3['invite_rewards']+ $level3_reward;
                                $parent_user3['freeze_rewards'] =  $parent_user3['freeze_rewards']+ $level3_reward;
                                $parent_user3['total_invite_rewards'] = $parent_user3['total_invite_rewards']+ $level3_reward;
                            }
                            $parent_user3->save();            
                        }  
                    }

                }                         
            }                 
        }      
    
    }
    //邮箱注册
    public function actionEmailRegister(){
        $request = Yii::$app->request;
        $email_num = $request->post('email');
         $email_num= Jinglan::check_email($email_num);
        $code = $request->post('code');
        $varcode = $request->post('varcode');
        $this->check_empty($varcode,'_The_Verification_Code_Can_Not_Be_Empty_');
        $password = $request->post('password');
        $this->check_empty($password,'_Password_Cannot_Be_empty_');
        if(strlen($password)<6){
            $this->error_message('_Enter_at_least_6_digits_of_the_password');
        }
        if(!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/',$password)){
            $this->error_message('必须同时包含字母和数字');
        }
        $repassword = $request->post('repassword');
        $this->check_empty($repassword,'_Confirm_Password_Must_Not_Be_Empty_');
        if($password  !== $repassword){
            $this->error_message('_The_Two_Password_Input_Is_Inconsistent_');
        }
      
        $member = Member::find()->where(['email'=>$email_num])->one();
         if(!empty($member)){
              $this->error_message('_MobilePhone_Exist_');
          }
      
          if(!empty($code)){
            $c = Member::find()->where(['code'=>$code])->one();
            if(empty($c)){
                //$this->error_message('邀请码无效!');
            }
            $varcode_result = EmailCode::find()->where( ['email'=>$email_num, 'varcode'=>$varcode,'type'=>1] )->one();
            if (!empty($varcode_result)){//验证成功
                $password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
                $member_model= new Member();
                $member_model->username = $email_num;
                $member_model->mobile_phone = '';
                $member_model->email = $email_num;
                $member_model->mobile_phone_status = 0;
                $member_model->password_hash = $password_hash;
                $member_model->code = StringHelper::random(6);
                $member_model->last_member = $c['id'];
                $member_model->nickname = StringHelper::random(8);
                $member_model->head_portrait = '/attachment/images/head_portrait.png';
                $member_model->created_at = time();
                $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
                $member_model->visit_count = 1;
                $member_model->last_time = 0;
                $member_model->last_ip = Yii::$app->request->getUserIP();

                $reward_status = Yii::$app->config->info('INVITE_REG_REWARD_STATUS');
                $reward = Yii::$app->config->info('INVITE_REG_REWARD');

                if($reward_status == 1){
                    $member_model->invite_rewards = $reward;
                    $member_model->freeze_rewards  =  $reward;
                    $member_model->total_invite_rewards  =  $reward;
                }

                if($member_model->save() > 0){
                    $os = strtolower($request->post('os'));
                    if (in_array($os, ['ios','android'])){
                        $user_id = $member_model->attributes['id'];
                        $group = 1;
                        $rst = AccessToken::setMemberInfo($group, $user_id);
                        $access_token = $rst['access_token'];
                        $from = $os;
                    }else{
                        $user_id = $member_model->attributes['id'];
                        $access_token = $member_model['access_token'];
                        $from = 'web';
                    }
                    $c['son_member'] = $c['son_member']+1;
                    $c->save();
                    $this->parentReward($code);
                    DSActivity::regReward($user_id);
                    $this->success_message(['access_token'=>$access_token,'from'=>$from]);
                }else{
                    $this->error_message('_Registration_Failed_Try_Later_');
                }
            }else{
                $this->error_message('_VerCode_Error_');
            }
        }else{
             $varcode_result = EmailCode::find()->where( ['email'=>$email_num, 'varcode'=>$varcode , 'type'=>1] )->one();
            if (!empty($varcode_result)){//验证成功
                $password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
                $member_model= new Member();
                $member_model->username = $email_num;
                $member_model->mobile_phone = '';
                $member_model->email = $email_num;
                $member_model->mobile_phone_status = 0;
                $member_model->password_hash = $password_hash;
                $member_model->code = StringHelper::random(6);
                $member_model->nickname = StringHelper::random(8);
                $member_model->head_portrait = '/attachment/images/head_portrait.png';
                $member_model->created_at = time();
                $member_model->access_token = Yii::$app->security->generateRandomString() . '_' . time();
                $member_model->visit_count = 1;
                $member_model->last_time = time();
                $member_model->last_ip = Yii::$app->request->getUserIP();
                if($member_model->save() > 0){
                    $os = strtolower($request->post('os'));
                    if (in_array($os, ['ios','android'])){
                        $user_id = $member_model->attributes['id'];
                        $group = 1;
                        $rst = AccessToken::setMemberInfo($group, $user_id);
                        $access_token = $rst['access_token'];
                        $from = $os;
                    }else{
                        $user_id = $member_model->attributes['id'];
                        $access_token = $member_model['access_token'];
                        $from = 'web';
                    }
                    DSActivity::regReward($user_id);                    
                    $this->success_message(['access_token'=>$access_token,'from'=>$from]);
                }else{
                    $this->error_message('_Registration_Failed_Try_Later_');
                }
            }else{
                $this->error_message('_VerCode_Error_');
            }
        }
    }
  
        //通过邮箱验证码找回密码
    public function actionEmailForgetPassword(){
        $request = Yii::$app->request;
        $email = $request->post('email');
        $email = Jinglan::check_email($email);
      
        $varcode = $request->post('varcode');
        $this->check_empty($varcode,'_The_Verification_Code_Can_Not_Be_Empty_');
        $password = $request->post('password');
        $this->check_empty($password,'_Password_Cannot_Be_empty_');
        if(strlen($password)<6){
            $this->error_message('_Enter_at_least_6_digits_of_the_password');
        }
        $repassword = $request->post('repassword');
        $this->check_empty($repassword,'_Confirm_Password_Must_Not_Be_Empty_');
        if($password  !== $repassword){
            $this->error_message('_The_Two_Password_Input_Is_Inconsistent_');
        }

        $member = Member::find()->where(['email'=>$email])->one();
        if(empty($member)){
            $this->error_message('_Please_first_register_');
        }

      $varcode_result = EmailCode::find()->where(['email'=>$email, 'varcode'=>$varcode])->one();
        if (!empty($varcode_result)){//验证成功

            $password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $member->password_hash = $password_hash;
            $member->updated_at = time();
            $member->access_token = Yii::$app->security->generateRandomString() . '_' . time();
            if($member->save(false)){
                $os = strtolower($request->post('os'));
                if (in_array($os, ['ios','android'])){
                    $user_id = $member->attributes['id'];
                    $group = 1;
                    $rst = AccessToken::setMemberInfo($group, $user_id);
                    $access_token = $rst['access_token'];
                    $from = $os;
                }else{
                    $access_token = $member['access_token'];
                    $from = 'web';
                }
                $this->success_message(['access_token'=>$access_token,'from'=>$from]);
                die();
            }else{
                $this->error_message('_Try_Again_Later_');
            }
        }else{
            $this->error_message('_VerCode_Error_');
        }
    }


    //忘记密码
    public function actionForgetPassword(){
        $request = Yii::$app->request;
        $mobile_phone = $request->post('mobile_phone');
        $mobile_phone = Jinglan::check_mobile_phone($mobile_phone);
        $varcode = $request->post('varcode');
        $this->check_empty($varcode,'_The_Verification_Code_Can_Not_Be_Empty_');
        $password = $request->post('password');
        $this->check_empty($password,'_Password_Cannot_Be_empty_');
        if(strlen($password)<6){
            $this->error_message('_Enter_at_least_6_digits_of_the_password');
        }
        $repassword = $request->post('repassword');
        $this->check_empty($repassword,'_Confirm_Password_Must_Not_Be_Empty_');
        if($password  !== $repassword){
            $this->error_message('_The_Two_Password_Input_Is_Inconsistent_');
        }

        $member = Member::find()->where(['mobile_phone'=>$mobile_phone,'mobile_phone_status'=>1])->one();
        if(empty($member)){
            $this->error_message('_Please_first_register_');
        }

        $varcode_result = Varcode::find()->where(['mobile_phone' => $mobile_phone, 'varcode'=>$varcode])->one();
        if (!empty($varcode_result)){//验证成功

            $password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $member->password_hash = $password_hash;
            $member->updated_at = time();
            $member->access_token = Yii::$app->security->generateRandomString() . '_' . time();
            if($member->save(false)){
                $os = strtolower($request->post('os'));
                if (in_array($os, ['ios','android'])){
                    $user_id = $member->attributes['id'];
                    $group = 1;
                    $rst = AccessToken::setMemberInfo($group, $user_id);
                    $access_token = $rst['access_token'];
                    $from = $os;
                }else{
                    $access_token = $member['access_token'];
                    $from = 'web';
                }
                $this->success_message(['access_token'=>$access_token,'from'=>$from]);
            }else{
                $this->error_message('_Try_Again_Later_');
            }
        }else{
            $this->error_message('_VerCode_Error_');
        }
    }
  
        

    //忘记密码手机验证
    public function actionForgetPasswordPhone(){
        $request = Yii::$app->request;
        $mobile_phone = $request->post('mobile_phone');
        $mobile_phone = Jinglan::check_mobile_phone($mobile_phone);
        $varcode = $request->post('varcode');
        $this->check_empty($varcode,'_The_Verification_Code_Can_Not_Be_Empty_');
        $member = Member::find()->where(['mobile_phone'=>$mobile_phone,'mobile_phone_status'=>1])->one();
        $access_token = $member['access_token'];
        if(empty($member)){
            $this->error_message('_Please_first_register_');
        }
        $varcode_result = Varcode::find()->where(['mobile_phone' => $mobile_phone, 'varcode'=>$varcode])->one();
        if(!empty($varcode_result)){
            $this->success_message(['access_token'=>$access_token]);
        }else{
            $this->error_message('_VerCode_Error_');
        }
    }

    public function actionCoinText(){
        $request = Yii::$app->request;
        $coin_symbol = $request->post('coin_symbol');
        $this->check_empty($coin_symbol,'货币简称不能为空');
        $coin = Coins::find()->select('coin_text')->where(['symbol'=>$coin_symbol])->one();
        $this->success_message(['coin_text'=>$coin['coin_text']]);
    }


    function gethost(){
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


    public function actionRecommend(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $this->check_empty($access_token,'_NOT_Empty_');
        $this->check_empty($os,'_NOT_Empty_');
        if (in_array($os, ['ios','android'])){
            $uinfo = $this->checkToken($access_token);
        }else{
            $uinfo = $this->memberToken($access_token);
        }
        
        $uid = $uinfo['id'];
        $member = Member::find()->where(['id'=>$uid])->one();
        if(!empty($member)){
            if(empty($member['code'])){
                $member['code'] = StringHelper::random(6);
                $member->save();
                $code = $member['code'];
            }else{
                $code = $member['code'];
            }
            $host = $this->gethost();
            if($os == 'ios'){
                $img = $host.'/api/register/qrcode?url='.urlencode($host.'/wap/share_register?os=ios&code='.$code);
                $img2 = $host.'/wap/share_register?os=ios&code='.$code;
                
            }elseif($os == 'android'){
                $img = $host.'/api/register/qrcode?url='.urlencode($host.'/wap/share_register?os=android&code='.$code);
                $img2 = $host.'/wap/share_register?os=android&code='.$code;

            }else{
                $this->error_message();
            }
            $this->success_message(['code'=>$code,'img'=>$img,'url'=>$img2]);
        }else{
            $this->error_message();
        }
    }

    public function actionRecommendMember(){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $os = strtolower($request->post('os'));
        $this->check_empty($access_token,'_NOT_Empty_');
        $this->check_empty($os,'_NOT_Empty_');
        if (in_array($os, ['ios','android'])){
            $uinfo = $this->checkToken($access_token);
        }else{
            $uinfo = $this->memberToken($access_token);
        }
        $uid = $uinfo['id'];
        $member = Member::find()->select(['id','nickname','head_portrait'])->where(['last_member'=>$uid])->asArray()->all();
        foreach($member as $k=>$v){
            $member[$k]['head_portrait'] = $this->get_user_avatar_url($member[$k]["head_portrait"]);
        }
        if(!empty($member)){
            $this->success_message($member);
        }else{
            $this->error_message();
        }
    }

    public function actionQrcode(){
        $request = Yii::$app->request;
        $url = $request->get('url');        
        Common::Qrcode($url,10);
        exit;
    }


  //开启谷歌二次验证
    public function actionOpenGoogleCheck (){
       $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $this->check_empty($access_token,'_NOT_Empty_');
       $uinfo = $this->checkToken($access_token);
      $var = Member::find()->where(['id'=>$uinfo['id']])->one();
      if($var['is_google_check'] == 1 ){
            $this->error_message('已經開啟了二次驗證，不用重複開啟。');
        }
      if(empty($var)){
        $this->error_message('_Please_first_register_');
      }
      //$old_type= $var['is_google_check'];
      //$var['is_google_check'] =1;
      
       if(!empty(  $var['google_check_key'] )){
            $vcode = $var['google_check_key'];
            $vcode_img_str = urldecode($var['google_check_img_key']); 
        }
         else{
            $g_auth = new GoogleAuthenticator();
            // 获取随机密钥
            $vcode  = $g_auth->createSecret();
            $vcode_img_str = urldecode( $g_auth->getQRCodeGoogleUrl($var['id'], $vcode )  );
            $var['google_check_key'] =  $vcode;
            $var['google_check_img_key'] = $vcode_img_str;
        }
           $isup=  $var->save();
          if($isup){  //||   $old_type==1 
            $this->success_message(['google_check_key'=>$vcode , 'google_check_img_key' =>$vcode_img_str ]);
        }else{
            $this->error_message('_VerCode_Error_');
        }
    }

    //关闭谷歌二次验证
    public function actionConfirmGoogleCheck (){
        $vcode = $this->checkccommon();
         if($vcode ==  1 ){
            $request = Yii::$app->request;
            $access_token = $request->post('access_token');
            $this->check_empty($access_token,'_NOT_Empty_');
            $uinfo = $this->checkToken($access_token);
            $var = Member::find()->where(['id'=>$uinfo['id']])->one();
            $var['is_google_check'] = 1;
             $isup=  $var->save();
             if($isup){
                $this->success_message();
             }else{
                $this->error_message('操作失敗');
             }
        }else if($vcode == 2){
           $this->error_message('_VerCode_Error_');
        }else{
           $this->error_message('_VerCode_Error_');
        }
    }

    //关闭谷歌二次验证
    public function actionCloseGoogleCheck (){
        $vcode = $this->checkccommon();
         if($vcode ==  1 ){
            $request = Yii::$app->request;
            $access_token = $request->post('access_token');
            $this->check_empty($access_token,'_NOT_Empty_');
            $uinfo = $this->checkToken($access_token);
            $var = Member::find()->where(['id'=>$uinfo['id']])->one();
            $var['is_google_check'] =0;
             $isup=  $var->save();
             if($isup){
                $this->success_message();
             }else{
                $this->error_message('操作失敗');
             }
        }else if($vcode == 2){
           $this->error_message('_VerCode_Error_');
        }else{
           $this->error_message('_VerCode_Error_');
        }
    }
    
      // 日常操作验证
    public function actionCheckGoogleCode(){
        $vcode = $this->checkccommon();
        if($vcode ==  1 ){
            $this->success_message();
        }else if($vcode == 2){
           $this->error_message('_VerCode_Error_');
        }else{
           $this->error_message('_VerCode_Error_');
        }
    }
   //谷歌验证公用函数
    public function checkccommon(){
       $request = Yii::$app->request;
       $access_token = $request->post('access_token');
       $varcode = $request->post('var_code');
       $this->check_empty($access_token,'_NOT_Empty_');
       $uinfo = $this->checkToken($access_token);
       $var = Member::find()->where(['id'=>$uinfo['id']])->one();
        if($var['is_google_check'] != 1){
             //$this->error_message('您還未開啟谷歌驗證');
        }
       $g_auth = new GoogleAuthenticator();
       $vcode  = $g_auth->getCode(  $var['google_check_key'] );
        if($vcode == $varcode){
            return 1;
        }else{
            return 2;
        }
    }

    //开启两步验证
    //关闭谷歌二次验证
    public function actionOpenSecondCheck (){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $this->check_empty($access_token,'_NOT_Empty_');
        $uinfo = $this->checkToken($access_token);
        $var = Member::find()->where(['id'=>$uinfo['id']])->one();
        if($var['two_step_open_status'] == 1){
            $this->error_message('你已經開啟過二次驗證了');            
        }
        $var['two_step_open_status'] = 1;
         $isup=  $var->save();
         if($isup){
            $this->success_message('','操作成功');
         }else{
            $this->error_message('操作失敗');
         }
    }

    //关闭两步验证

    public function actionCloseSecondCheck (){
        $request = Yii::$app->request;
        $access_token = $request->post('access_token');
        $this->check_empty($access_token,'_NOT_Empty_');
        $uinfo = $this->checkToken($access_token);
        $var = Member::find()->where(['id'=>$uinfo['id']])->one();
        if($var['two_step_open_status'] == 0){
            $this->error_message('你已經關閉過二次驗證了');            
        }
        $var['two_step_open_status'] = 0;
         $isup=  $var->save();
         if($isup){
            $this->success_message('','操作成功');
         }else{
            $this->error_message('操作失敗');
         }
    }    
}
