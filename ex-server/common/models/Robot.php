<?php

namespace common\models;

use api\models\ExchangeCoins;
use Yii;

/**
 * This is the model class for table "jl_start_page".
 *
 * @property int $id
 * @property string $title 标题
 * @property string $img 图片路径
 * @property string $url 链接
 * @property int $status 启用状态,默认0未启用,1启用,2已删除
 * @property int $add_time 添加时间
 */
class Robot extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'jl_robot';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['market_id', 'small_money','big_money','big_count','small_count','otime','ctime','intime','ctime','intime','simulate_status','robot_set_open','robot_set_close','robot_set_high','robot_set_low'], 'required'],
            [['uid', 'status'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => '用户ID',
            'market_id' => '交易市场',
            'market' => '交易市场',
            'small_money' => '当前最低出价',
            'big_money' => '当前最高出价',
            'small_count' => '最小单笔交易数量',
            'big_count' => '最大单笔交易数量',
            'intime' => '间隔时间',
            'status' => '状态',
            'simulate_status'=>'是否模拟K线',
            'otime' => '开始交易时间',
            'ctime' => '关闭交易时间',
            'robot_set_open' => '设定机器人开盘价',
            'robot_set_close' => '设定机器人收盘价',
            'robot_set_high' => '设定机器人最高价',
            'robot_set_low' => '设定机器人最低价',
        ];
    }

    public function getExchangeCoins(){
        return $this->hasOne(ExchangeCoins::className(),['id' => 'market_id']);
    }
}