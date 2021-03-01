<?php

namespace jianyan\basics\common\models\wechat;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%wechat_rule_stat}}".
 *
 * @property string $id
 * @property string $rule_id
 * @property string $hit
 * @property string $append
 * @property string $updated
 */
class RuleStat extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_rule_stat}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rule_id', 'hit', 'append', 'updated'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'        => 'ID',
            'rule_id'   => '规则id',
            'hit'       => '统计数量',
            'append'    => '创建时间',
            'updated'   => '修改时间',
        ];
    }

    /**
     * 插入今日规则统计
     * @param $rule_id
     */
    public static function setStat($rule_id)
    {
        $ruleStat = RuleStat::find()
            ->where(['rule_id'=> $rule_id, 'append' => strtotime(date('Y-m-d'))])
            ->one();

        if($ruleStat)
        {
            $ruleStat->hit = $ruleStat->hit + 1;
        }
        else
        {
            $ruleStat = new RuleStat();
            $ruleStat->rule_id = $rule_id;
        }

        $ruleStat->save();
    }

    /**
     * 关联规则
     * @return \yii\db\ActiveQuery
     */
    public function getRule()
    {
        return $this->hasOne(Rule::className(),['id' => 'rule_id']);
    }

    /**
     * @return array
     * 行为插入时间戳
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['updated'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated'],
                ],
            ],
        ];
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if($this->isNewRecord)
        {
            $this->append = strtotime(date('Y-m-d'));
        }

        return parent::beforeSave($insert);
    }
}
