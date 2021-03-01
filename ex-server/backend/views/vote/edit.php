<?php
use yii\widgets\ActiveForm;
use dosamigos\datetimepicker\DateTimePicker;

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => 'IEO', 'url' => ['list']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5><?= $this->title ?>IEO</h5>
                </div>
                <div class="ibox-content">
                    <?php 
                        $model->status = 1;
                        $form = ActiveForm::begin(); 
                    ?>
                    <?php $form = ActiveForm::begin(); ?>
                    <div class="col-sm-12">
                        <div  id="vote" class="formula-hint"  title="提示" data-container="body" data-toggle="popover" data-placement="top" data-content="该币种已添加"></div>
                        
                        <?= 
                            $model->isNewRecord ?
                               ""
                           :
                                 $form->field($model, 'id')->textInput(['readonly' => true,'value'=>$model->id])                                 
                            ;
                        ?>
                        <?= $form->field($model, 'title')->textInput()->label("标题") ?>
                        <?= $form->field($model, 'coin_symbol')->textInput() ?>
                        <?= $form->field($model, 'coin_name')->textInput() ?>
                        <?= $form->field($model, 'coin_duihuan_money')->textInput(['placeholder' => '兑换所需币种'])->label('兑换所需币种') ?>
                        <?= $form->field($model, 'coin_num')->textInput(['placeholder' => '可兑换总数量'])->label("可兑换总数量") ?>
                        <?= $form->field($model, 'coin_duihuan_num')->textInput(['placeholder' => '剩余数量'])->label("剩余可兑换数量") ?>
                        <?= $form->field($model, 'coin_duihuan_min')->textInput(['placeholder' => '最小兑换数量'])->label("最小兑换数量") ?>
                        <?= $form->field($model, 'coin_duihuan_max')->textInput(['placeholder' => '最大兑换数量'])->label("最大兑换数量") ?>
                        <?= $form->field($model, 'start_time')->textInput(['value' => date("Y-m-d H:i:s", $model->start_time)])->label("开始时间") ?>
                        <?= $form->field($model, 'end_time')->textInput(['value' => date("Y-m-d H:i:s", $model->end_time)])->label("结束时间") ?>
                        <?= $form->field($model, 'introduce')->textInput(['placeholder' => '简介'])->label('简介') ?>
                        <?= $form->field($model, 'img_small')->widget('backend\widgets\webuploader\Image', [
                            'boxId' => 'img_small',
                            'options' => [
                                'multiple'   => false,
                            ]
                        ])->label('列表显示图片') ?>
                        <?= $form->field($model, 'img_big')->widget('backend\widgets\webuploader\Image', [
                            'boxId' => 'img_big',
                            'options' => [
                                'multiple'   => false,
                            ]
                        ])->label('项目详情显示图片') ?>


                        <?= $form->field($model, 'info')->widget(\crazydb\ueditor\UEditor::className())->label('项目介绍') ?>


                        <div class="hr-line-dashed"></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12 text-center">
                            <button class="btn btn-primary" type="submit">保存内容</button>
                            <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                        </div>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

    $(function () {
        $("#otccoinlist-coin_name").blur(function () {
            var coin_id = $(this).val()
            $.ajax({
                url : "check-coin",
                type : "POST",
                data : {id:coin_id},
                success: function (result) {
                    result = $.parseJSON(result)
                    if(result.code != 200){
                       $("#coin").popover('show')
                        var obj =$("#coin")
                        // 隐藏弹框
                        popHide(obj)
                        $("#otccoinlist-coin_name").val('')
                    }
                }
            })
        })
        function popHide(obj){
            if(obj.attr('aria-describedby') != null){
                var time = setTimeout(function () {
                    $("#coin").popover("hide");
                },1000);
            }
        }
    })
</script>
