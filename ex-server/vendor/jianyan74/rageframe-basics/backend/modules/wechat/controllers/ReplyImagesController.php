<?php
namespace jianyan\basics\backend\modules\wechat\controllers;

use jianyan\basics\common\models\wechat\Rule;
use jianyan\basics\common\models\wechat\ReplyImages;

/**
 * 图片回复控制器
 *
 * Class ReplyImagesController
 * @package jianyan\basics\backend\modules\wechat\controllers
 */
class ReplyImagesController extends RuleController
{
    public $_module = Rule::RULE_MODULE_IMAGES;

    /**
     * 返回模型
     *
     * @param $id
     * @return array|ReplyImages|null|\yii\db\ActiveRecord
     */
    protected function findModel($id)
    {
        if (empty($id))
        {
            return new ReplyImages;
        }

        if (empty(($model = ReplyImages::find()->where(['rule_id'=>$id])->one())))
        {
            return new ReplyImages;
        }

        return $model;
    }
}
