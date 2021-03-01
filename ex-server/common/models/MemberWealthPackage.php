<?php
namespace common\models;

use yii\db\ActiveRecord;

class MemberWealthPackage extends ActiveRecord{


    public static function tableName(){
        return "{{%member_wealth_package}}";
    }

    public function rules(){
        return [
            [['coin_symbol','name','period','min_num','max_num','type','day_profit','icon'], 'required'],
        ];
    }


    public function attributeLabels(){
        return [
            'type' => '类型',
            'icon' => '图标',
            'coin_symbol' => '币种',
            'name' => '套餐名称',
            'period' => '周期(天)',
            'day_profit' => '日利率(%)',
            'min_num' => '购买最低数量',
            'max_num' => '购买最高数量',
        ];
    }

}