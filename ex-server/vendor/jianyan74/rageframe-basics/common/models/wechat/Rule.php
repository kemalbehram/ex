<?php

namespace jianyan\basics\common\models\wechat;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%wechat_rule}}".
 *
 * @property string $id
 * @property string $name
 * @property string $module
 * @property string $displayorder
 * @property integer $status
 * @property integer $append
 * @property integer $updated
 */
class Rule extends ActiveRecord
{
    /**
     * 模块类别
     */
    const RULE_MODULE_BASE = 'basic';
    const RULE_MODULE_NEWS = 'news';
    const RULE_MODULE_MUSIC = 'music';
    const RULE_MODULE_IMAGES = 'images';
    const RULE_MODULE_VOICE = 'voice';
    const RULE_MODULE_VIDEO = 'video';
    const RULE_MODULE_ADDON = 'addon';
    const RULE_MODULE_USER_API = 'user-api';
    const RULE_MODULE_WX_CARD = 'wxcard';
    const RULE_MODULE_DEFAULT = 'default';

    /**
     * @var array
     * 说明
     */
    public static $moduleExplain = [
        self::RULE_MODULE_BASE => '文字回复',
        self::RULE_MODULE_IMAGES => '图片回复',
        self::RULE_MODULE_NEWS => '图文回复',
        // self::RULE_MODULE_MUSIC => '音乐回复',
        self::RULE_MODULE_VOICE => '语音回复',
        self::RULE_MODULE_VIDEO => '视频回复',
        // self::RULE_MODULE_ADDON => '模块回复',
        self::RULE_MODULE_USER_API => '自定义接口回复',
        // self::RULE_MODULE_WX_CARD => '微信卡卷回复',
         self::RULE_MODULE_DEFAULT => '默认回复',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_rule}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['append', 'updated'], 'integer'],
            [['module','name'], 'required'],
            [['status'], 'integer'],
            [['name', 'module'], 'string', 'max' => 50],
            ['displayorder','number','min'=>0,'max'=> 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'name'          => '回复规则名称',
            'module'        => '模块',
            'displayorder'  => '优先级',
            'status'        => '状态',
            'append'        => '创建时间',
            'updated'       => '修改时间',
        ];
    }

    /**
     * 删除规则
     *
     * @param $module
     * @return false|int
     */
    public static function deleted($module)
    {
        $model = self::find()
            ->where(['module' => $module])
            ->one();

        if($model)
        {
            return $model->delete();
        }
    }

    /**
     * 删除其他数据
     */
    public function afterDelete()
    {
        $id = $this->id;
        // 关键字删除
        RuleKeyword::deleteAll(['rule_id' => $id]);
        // 规则统计
        RuleStat::deleteAll(['rule_id' => $id]);
        // 关键字规则统计
        RuleKeywordStat::deleteAll(['rule_id' => $id]);

        // 删除关联数据
        switch ($this->module)
        {
            case  self::RULE_MODULE_BASE :
                ReplyBasic::deleteAll(['rule_id' => $id]);
                break;

            case  self::RULE_MODULE_NEWS :
                ReplyNews::deleteAll(['rule_id' => $id]);
                break;

            case  self::RULE_MODULE_MUSIC :
               // ReplyBasic::deleteAll(['rule_id'=>$id]);
                break;

            case  self::RULE_MODULE_IMAGES :
                ReplyImages::deleteAll(['rule_id' => $id]);
                break;

            case  self::RULE_MODULE_VOICE :
                ReplyVoice::deleteAll(['rule_id' => $id]);
                break;

            case  self::RULE_MODULE_VIDEO :
                ReplyVideo::deleteAll(['rule_id' => $id]);
                break;

            case  self::RULE_MODULE_USER_API :
                ReplyUserApi::deleteAll(['rule_id' => $id]);
                break;

            case  self::RULE_MODULE_WX_CARD :
                // ::deleteAll(['rule_id'=>$id]);
                break;

            default :
                ReplyAddon::deleteAll(['rule_id' => $id]);
                break;
        }

        parent::afterDelete();
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if($this->id)
        {
            RuleKeyword::updateAllDisplayorder($this->displayorder,$this->status,$this->id);
        }

        return parent::beforeSave($insert);
    }

    /**
     * 查询规则标题
     *
     * @param $rule_id
     * @return string
     */
    public static function findRuleTitle($rule_id)
    {
        $rule = Rule::findOne($rule_id);
        return $rule ? $rule->name : '规则被删除';
    }

    /**
     * 关联关键字
     */
    public function getRuleKeyword()
    {
        return $this->hasMany(RuleKeyword::className(), ['rule_id' => 'id'])->orderBy('type asc');
    }

    /**
     * 行为插入时间戳
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
