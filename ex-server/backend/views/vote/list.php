<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'IEO';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>IEO</h5>
                    <div class="ibox-tools">
                        <a class="btn btn-primary btn-xs" href="<?= Url::to(['edit'])?>">
                            <i class="fa fa-plus"></i>  新增IEO
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>标题</th>
                            <th>待兑换币种</th>
                            <th>兑换所需币种</th>
                            <th>可兑换总数量/剩余可兑换数量</th>
                            <th>最小兑换数量/最大兑换数量</th>
                            <th>开始时间/结束时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($models as $model){ ?>
                            <tr>
                                <td><?= $model['id']?></td>                            	
                                <td><?= $model['title']?></td>
                                <td><?= $model['coin_symbol']?></td>
                                <td><?= $model['coin_duihuan_money']?></td>
                                <td><?= $model['coin_num']?>/<?= $model['coin_duihuan_num']?></td>
                                <td><?= $model['coin_duihuan_min']?>/<?= $model['coin_duihuan_max']?></td>
                                <td><?= date('Y-m-d H:i:s', $model['start_time']) ?>/<?= date('Y-m-d H:i:s', $model['end_time']) ?></td>

                                <td>
                                    <a href="<?= Url::to(['edit','id'=>$model['id']])?>""><span class="btn btn-info btn-sm">修改</span></a>&nbsp;
                                    <a href="<?= Url::to(['delete','id'=>$model['id']])?>"  onclick="rfDelete(this);return false;"><span class="btn btn-warning btn-sm">删除</span></a>&nbsp;
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-sm-12">
                            <?= LinkPager::widget([
                                'pagination'        => $Pagination,
                                'maxButtonCount'    => 5,
                                'firstPageLabel'    => "首页",
                                'lastPageLabel'     => "尾页",
                                'nextPageLabel'     => "下一页",
                                'prevPageLabel'     => "上一页",
                            ]);?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<script>


    $(function () {
        $(".coin_text").click(function () {
            var textarea_html = $(this).attr('data-text');

            var coin_name = $(this).attr('data-name');
            $("#myModalLabel").html(coin_name);
            $("#coin_text_modal").html(textarea_html)
        })


        $(".enable").click(function() {
            var id = $(this).attr("data-id");
            swal({
                title: "确定吗？",
                text: "真的要启用吗！",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "启用！",
                closeOnConfirm: false
            },function () {
                $.ajax({
                    url: "coin-enable",
                    type: "POST",
                    data: {id: id, status: 1},
                    success: function (result) {
                        result = $.parseJSON(result)
                        if (result.code == 200) {
                            rfSuccess('启用', result.message);
                        } else {
                            rfError('启用', result.message);
                        }
                    }
                });
            });
        });
        $(".disable").click(function(){
            var id = $(this).attr("data-id");
            swal({
                title: "确定吗？",
                text: "真的要禁用吗！",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "禁用！",
                closeOnConfirm: false
            },function () {
                $.ajax({
                    url:"coin-enable",
                    type:"POST",
                    data:{id:id,status:0},
                    success : function(result) {
                        result = $.parseJSON(result)
                        if(result.code == 200){
                            rfSuccess('禁用', result.message);
                        }else{
                            rfError('禁用', result.message);
                        }
                    }
                });
            });
        });
    })

</script>
