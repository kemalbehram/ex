<?php
namespace jianyan\basics\backend\modules\sys\models;

use Yii;
use jianyan\basics\common\models\sys\Manager;
use jianyan\basics\common\models\sys\ActionLog;

/**
 * Class LoginForm
 * @package jianyan\basics\common\models\sys
 */
class LoginForm extends \common\models\base\LoginForm
{
    public $verifyCode;

    /**
     * 默认登录失败3次显示验证码
     *
     * @var int
     */
    public $attempts = 3;

    /**
     * 统计登录次数
     *
     * @var
     */
    public $counter;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
            ['password', 'validateIp'],
            ['verifyCode', 'captcha', 'on' => 'captchaRequired'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'username'   => '用戶名',
            'rememberMe' => '記住我',
            'password'   => '密碼',
            'verifyCode' => '驗證碼',
        ];
    }

    /**
     * 验证ip地址是否正确
     *
     * @param $attribute
     * @param $params
     */
    public function validateIp($attribute)
    {
        $ip = Yii::$app->request->userIP;
        $ipList =  Yii::$app->config->info('SYS_ALLOW_IP');
        if(!empty($ipList))
        {
            $value  = explode(",",$ipList);
            if(!in_array($ip,$value))
            {
                // ip不正确强行登陆
                Yii::$app->actionlog->addLog(ActionLog::ACTION_FORBID_IP,"login","账号:".$this->username);

                $this->addError($attribute,'禁止登陆');
            }
        }
    }

    /**
     * @return null|static
     */
    public function getUser()
    {
        if ($this->_user === null)
        {
            $this->_user = Manager::findByUsername($this->username);
        }

        return $this->_user;
    }

    /**
     * 登陆
     *
     * @return bool
     */
    public function login()
    {
        if ($this->validate() && Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0))
        {
            Yii::$app->session->remove('loginCaptchaRequired');

            return true;
        }
        else
        {
            $this->counter = Yii::$app->session->get('loginCaptchaRequired') + 1;
            Yii::$app->session->set('loginCaptchaRequired', $this->counter);
            if ($this->counter >= $this->attempts)
            {
                $this->setScenario("captchaRequired");
            }

            return false;
        }
    }
}
