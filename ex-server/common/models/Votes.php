<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/17
 * Time: 11:14
 */


namespace common\models;


use yii\db\ActiveRecord;

class Votes extends ActiveRecord{


    public static function tableName(){
        return "{{%vote}}";
    }

    public function rules(){
        return [
            [['coin_symbol','coin_name','introduce'],'required'],
            [['count','num','rate'], 'number'],
            [['vote_status'],'integer'],
        ];
    }


    public function attributeLabels(){
        return [
            'id'                =>      '主键ID',
            'coin_symbol'       =>      '币种符号',
            'coin_name'         =>      '币种名称',
            'introduce'        =>      '币种介绍',
            'count'    =>      '投票达标次数',
            'num'      =>      '投票达标人数',
            'rate'     =>      '投标达标率',
            'vote_status'     =>   '投票状态',
        ];
    }

}