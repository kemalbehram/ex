<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;
use dosamigos\datetimepicker\DateTimePicker;

$this->title = '列表';
$this->params['breadcrumbs'][] = ['label' =>  $this->title];

$starttime = '';
$endtime = '';
if (!empty($_GET['starttime'])) {
    $starttime = $_GET['starttime'];
}
if (!empty($_GET['endtime'])) {
    $endtime = $_GET['endtime'];
}
$status=['已失效','正常','已下架'];
$type=['','','活期','定期','锁仓'];
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>列表</h5>
                    <div class="ibox-tools">
                        <a class="btn btn-primary btn-xs" href="<?= Url::to(['edit'])?>">
                            <i class="fa fa-plus"></i>  新增套餐
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>编号</th>
                            <th>类型</th>
                            <th>ICON</th>
                            <th>币种</th>
                            <th>名称</th>
                            <th>周期</th>
                            <th>日利率(%)</th>
                            <th>最低购买额度</th>
                            <th>最大购买额度</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($data as $model){ ?>
                            <tr>
                                <td><?= $model['id'] ?></td>
                                <td><?= $type[$model['type']] ?></td>
                                <td><img src="<?= $model['icon']?>" style="width: 45px;height: 45px"></td>
                                <td><?= $model['coin_symbol'] ?></td>
                                <td><?= $model['name'] ?></td>
                                <td><?= $model['period'] ?></td>
                                <td><?= $model['day_profit'] ?></td>
                                <td><?= $model['min_num'] ?></td>
                                <td><?= $model['max_num'] ?></td>
                                <td><?= $status[$model['status']] ?></td>
                                <td>
                                    <a href="<?= Url::to(['edit','id'=>$model['id']])?>""><span class="btn btn-info btn-sm">修改</span></a>
                                    <a href="<?= Url::to(['delete','id'=>$model['id']])?>"  onclick="rfDelete(this);return false;"><span class="btn btn-warning btn-sm">删除</span></a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 模态框（Modal） -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="width: 800px">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="myModalLabel">实名认证</h4>
            </div>
            <div class="modal-body" style="text-align: center;">
                <img id="img1" src="" class="img-thumbnail" style="width: 350px">
                &nbsp;&nbsp;&nbsp;
                <img id="img2" src="" class="img-thumbnail" style="width: 350px">
            </div>
        </div>
    </div>
</div>

<script>
    $(".real_name").click(function(){
        var img1 = $(this).attr("data-img");
        var img2 = $(this).attr("data-img2");
        var name = $(this).attr("data-name");
        $("#img1").attr('src',img1);
        $("#img2").attr('src',img2);
        $("#myModalLabel").html(name);
    });

    // 更新费率
   function updateRate($data)
   {
        var update_id   = $data.getAttribute('data-id');
        var update_rate =  Number($data.value); 
        var update_type = $data.getAttribute('data-type');
        if (!isNaN(update_rate)){
            $.ajax({
                type:"post",
                url:"<?= Url::to(['change-rate'])?>",
                dataType: "json",
                data: {id:update_id,rate:update_rate,type:update_type},
                success: function(data){
                    if(data.code == 200) {
                        window.location.reload();
                        // layer.alert('更新排序字段成功!', {icon: 1}, function(){
                        //     window.location.reload();
                        // });
                    }else{
                        layer.alert('更新分成比例失败！', {icon: 2});
                        console.log(data.message);
                    }
                },
                error: function(e){
                    layer.alert('更新分成比例失败！', {icon: 2});
                    console.log(e);
                }
            });

        }else{
            layer.alert('汇率字段必须是数字！', {icon: 2});
        }
   }

</script>
