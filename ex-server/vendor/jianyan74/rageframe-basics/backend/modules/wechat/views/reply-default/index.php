<?php
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = '关注/默认回复';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="tabs-container">
                <ul class="nav nav-tabs">
                    <?= $this->render('/common/rule-nav',[
                        'nav_type' => 3,
                    ])?>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="panel-body">
                            <?php $form = ActiveForm::begin(); ?>
                            <div class="col-sm-12">
                                <?= $form->field($model, 'follow_content')->textInput()->hint('设置用户添加公众帐号好友时，发送的欢迎信息。') ?>
                                <?= $form->field($model, 'default_content')->textInput()->hint('当系统不知道该如何回复粉丝的消息时，默认发送的内容。') ?>
                                <div class="hr-line-dashed"></div>
                            </div>
                            <div class="form-group">　
                                <div class="col-sm-4 col-sm-offset-2">
                                    <button class="btn btn-primary" type="submit">保存内容</button>
                                </div>
                            </div>
                            <?php ActiveForm::end(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

