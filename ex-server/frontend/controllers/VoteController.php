<?php
namespace frontend\controllers;

use Yii;
use jianyan\basics\common\models\sys\ArticleSingle;
use common\models\Article;
use common\models\base\AccessToken;
use api\models\Member;
use common\controllers\ActiveController;
use api\models\MemberWallet;
use yii\web\Session;

/**
 * Index controller
 */
class VoteController extends IController
{
    /**
     * 系统首页
     * @return string
     */
    public function actionIndex()
    {
      $header['title']= "投票上币"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
      $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
      $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
      $view = Yii::$app->view;
      $view->params['header']=$header;

      $access_token = Yii::$app->request->get('token');
      $os = strtolower(Yii::$app->request->get('os'));

      if (!empty($access_token)) {
          $access_token_info = AccessToken::findIdentityByAccessToken($access_token);
          if (empty($access_token_info)){
              $access_token_info = Member::findAccessToken($access_token);
              if (empty($access_token_info)){
                  $member_model = [];
              }else{
                  $user_id = $access_token_info->attributes['id'];
                  $uinfo['uid'] = $user_id;
                  $uinfo['access_token'] = $access_token;
                  $member_model = $this->getUserInfoById($user_id);
              }
          }else{
              $user_id = $access_token_info->attributes['user_id'];
              $uinfo['uid'] = $user_id;
              $uinfo['access_token'] = $access_token;
              $member_model = $this->getUserInfoById($user_id);
          }

          if (!empty($member_model)) {
            $session = new Session;
            $session->open();
            $session["user"]= $member_model['id'];
            $session["email"]= $member_model['email'];
            $session["access_token"]= $member_model->access_token;
            $access_token = $member_model->access_token;
            $from = 'web';
          }
      }


      $data = (new \yii\db\Query())->from('jl_vote')->where(['vote_status' => 2])->all();
      if(empty($data)){
        $data = [];
      }


      return $this->render('index', [
          'data'             => $data,
      ]);
    }

    public function actionDetial()
    {
      
      $header['title']= "投票上币"." - ".Yii::$app->config->info('WEB_SITE_TITLE') ;
      $header['keywords']= Yii::$app->config->info('WEB_SITE_KEYWORD');
      $header['descripition']= Yii::$app->config->info('WEB_SITE_DESCRIPTION');  
      $view = Yii::$app->view;
      $view->params['header']=$header;          


      $data = (new \yii\db\Query())->from('jl_vote')->where(['id' => $_GET['id']])->one();
      if(empty($data)){
        $data = [];
      }



      return $this->render('detial', [
          'data'             => $data,
      ]);
    }



  //根据用户id获取用户信息
  protected function getUserInfoById($id){
    $where['id']=$id;
    $select = 'id,username,password_hash,nickname,email,head_portrait,verified_status,mobile_phone,mobile_phone_status,status,otc_merchant,code,son_1_num,son_2_num,son_3_num,invite_rewards,invite_fee_rewards,total_invite_rewards,freeze_rewards,frozen_rewards,access_token';
    $result = Member::find()->select($select)->where($where)->asArray()->one();
    if($result){
      $result['head_portrait'] = $this->get_user_avatar_url($result["head_portrait"]);
      $result['exchange_password'] = empty($result['password_hash']) ? 0 : 1;
      unset($result['password_hash']);
      unset($result['status']);
      return $result;
    }
    return '';
  }

  protected function get_user_avatar_url($avatar){
    if($avatar){
      if(strpos($avatar, "http")===0){
        return $avatar;
      }else{
        return \Yii::$app->request->hostInfo . $avatar;
      }
    }else{
      return \Yii::$app->request->hostInfo . '/attachment/images/head_portrait.png';
    }
  }

}
