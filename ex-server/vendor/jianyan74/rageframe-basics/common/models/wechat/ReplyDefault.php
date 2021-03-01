<?php
namespace jianyan\basics\common\models\wechat;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use jianyan\basics\common\models\sys\Addons;

/**
 * This is the model class for table "{{%wechat_reply_default}}".
 *
 * @property integer $id
 * @property string $follow_content
 * @property string $default_content
 * @property integer $append
 * @property integer $updated
 */
class ReplyDefault extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_reply_default}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['append', 'updated'], 'integer'],
            [['follow_content', 'default_content'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'follow_content'    => '欢迎信息关键字',
            'default_content'   => '默认回复关键字',
            'append'            => '创建时间',
            'updated'           => '修改时间',
        ];
    }

    /**
     * 返回回复信息
     * 如果是特殊消息进来的的$message是一个数组
     *
     * @param string $type 触发的类型 follow:关注;text:文字信息
     * @param null $message 用户发送的内容
     * @return array|bool|mixed|null
     */
    public static function defaultReply($type = 'follow', $message = null)
    {
        $defaultModel = self::findDefault();
        // 默认回复内容
        $reply = $type == 'follow' ? $defaultModel->follow_content :  $defaultModel->default_content;

        switch ($type)
        {
            /** 系统关注回复 **/
            case "follow" :

                $default = RuleKeyword::match($reply);
                if($default)
                {
                    return $default;
                }

                break;

            /** 文字回复关注 **/
            case "text" :

                // 查询用户关键字匹配
                $default = RuleKeyword::match($message);
                if($default == false)
                {
                    // 系统默认回复
                    $default = RuleKeyword::match($reply);
                    $default && $default['module'] = Rule::RULE_MODULE_DEFAULT;
                }

                // 查询关键字并返回
                if ($default)
                {
                    return $default;
                }

                break;

            /** 特殊消息回复 **/
            case "special" :

                $reply = null;
                $msgType = $message['MsgType'];
                $special = Setting::getSetting('special');

                if (isset($special[$msgType]))
                {
                    // 关键字
                    if ($special[$msgType]['type'] == Setting::SPECIAL_TYPE_KEYWORD)
                    {
                        if ($default = RuleKeyword::match($special[$msgType]['content']))
                        {
                            return $default;
                        }
                    }
                    else
                    {
                        // 模块处理
                        !empty($special[$msgType]['selected']) && $reply = Addons::getWechatMessage($message, $special[$msgType]['selected']);
                        if ($reply)
                        {
                            return [
                                'content' => $reply,
                                'module'  => $special[$msgType]['selected']
                            ];
                        }
                    }
                }

                break;
        }

        // 返回默认回复
        return $reply ? ['content' => $reply, 'module'  => Rule::RULE_MODULE_DEFAULT] : false;
    }

    /**
     * 查询默认回复
     *
     * @return array|ReplyDefault|null|ActiveRecord
     */
    public static function findDefault()
    {
        if (!empty(($model = ReplyDefault::find()->one())))
        {
            return $model;
        }

        return new ReplyDefault();
    }

    /**
     * 行为
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['append', 'updated'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated'],
                ],
            ],
        ];
    }
}
