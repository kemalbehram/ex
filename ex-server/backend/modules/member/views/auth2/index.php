<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = '实名认证';
$this->params['breadcrumbs'][] = ['label' =>  $this->title];
?>
<div class="wrapper wrapper-content animated fadeInRight">
    <!--  search begin  -->
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>查询</h5>
                </div>
                <div class="ibox-content">
                    <form action="" method="get" class="form-horizontal" role="form" id="form">

                        <div class="form-group">
                            <label class="col-xs-12 col-sm-2 col-md-2 control-label">关键字</label>
                            <div class="col-sm-8 col-xs-12 input-group m-b">
                                <input type="hidden" class="form-control" name="type" value="<?= $type?>" />
                                <input type="text" class="form-control" name="keyword" value="<?= $keyword?>" />
                                <span class="input-group-btn">
                                    <button class="btn btn-white"><i class="fa fa-search"></i> 搜索</button>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--  search end  -->
    <!--  list begin  -->
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>认证列表</h5>
                </div>
                <div class="ibox-content">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>UID</th>                            
                            <th>用户昵称</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>银行代号</th>
                            <th>分行代号</th>
                            <th>收款人</th>
                            <th>银行卡号</th>
                            <th>认证状态</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($models as $model){ ?>
                            <tr>
                                <td><?= $model->id?></td>
                                <td><?= $model->id?></td>
                                <td><?=$model->nickname?></td>
                                <td><?=$model->email?></td>
                                <td><?=$model->mobile_phone?></td>
                                <td><?= $model->bank_no?></td>
                                <td><?= $model->bank_branch_no?></td>
                                <td><?= $model->bank_payee?></td>
                                <td><?= $model->bank_account?></td>
                                <td><span style="color:<?= $status_color[$model->bank_status]?>;"><?= $status[$model->bank_status]?></span></td>
                                <td>
                                    <?php $auth = in_array($model->bank_status,[-1,1]) ? 'operated' : 'operation';?>
                                    <a href="#" data-id="<?=$model->id?>" data-name="<?=$model->id?>" data-type="<?=$auth?>" data-status="<?=$model->bank_status?>" data-toggle="modal" data-target="#myModal" class="real_name"><span class="btn btn-info btn-sm"><?=$auth == 'operated' ? '查看' : '审核';?></span></a>&nbsp;
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
    <!--  list end  -->
</div>
<!-- 模态框（Modal） -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 800px">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">实名认证</h4>
            </div>
            <div class="modal-footer">
                <button type="button" id="default" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" id="fail" class="btn btn-danger">认证失败</button>
                <button type="button" id="success" class="btn btn-primary">认证通过</button>
            </div>
        </div>
    </div>
</div>
<!-- /.modal -->
<script>
    $(".real_name").click(function(){
        var id = $(this).attr("data-id");
        var name = $(this).attr("data-name");
        var img1 = $(this).attr("data-img");
        var img2 = $(this).attr("data-img2");
        var img3 = $(this).attr("data-img3");
        var type = $(this).attr("data-type");
        var status = $(this).attr("data-status");
        $("#img1").attr('src',img1);
        $("#img2").attr('src',img2);
        $("#img3").attr('src',img3);
        $("#myModalLabel").html(name);
        if(type == 'operation') {
            $("#fail").attr('data-id', id);
            $("#success").attr('data-id', id);
        }else{
            $("#fail").remove();
            $("#success").remove();
            $("#default").removeClass('btn-default');
            if(status == 2) {
                $("#default").html('已通过');
                $("#default").addClass('btn-primary');
            }else{
                $("#default").html('已拒绝');
                $("#default").addClass('btn-danger');
            }
        }
    });
    $(function() {
        $('#myModal').on('hide.bs.modal',
            function() {
                window.location.reload()
            })
    });
    $("#fail").click(function(){
        var id = $(this).attr("data-id");
        $.ajax({
            url:"examine",
            type:"POST",
            data:{id:id,type:'fail'},
            success : function(result) {
                result = $.parseJSON(result)
                if(result.code == 200){
                    rfSuccess('认证不通过', result.message);
                }else{
                    rfError('认证不通过', result.message);
                }
            }
        });
    });
    $("#success").click(function(){
        var id = $(this).attr("data-id");
        $.ajax({
            url:"examine",
            type:"POST",
            data:{id:id,type:'success'},
            success : function(result) {
                result = $.parseJSON(result)
                if(result.code == 200){
                    rfSuccess('认证通过', result.message);
                }else{
                    rfError('认证通过', result.message);
                }
            }
        });
    });
</script>
