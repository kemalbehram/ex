<?php
namespace common\models;

use yii\db\ActiveRecord;

class MemberActivity extends ActiveRecord{


    public static function tableName(){
        return "{{%member_activity}}";
    }

    public function rules(){
        return [
            
        ];
    }


    public function attributeLabels(){
        return [

        ];
    }

}