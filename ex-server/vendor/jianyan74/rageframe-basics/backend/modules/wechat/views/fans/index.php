<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;
use jianyan\basics\common\models\wechat\Fans;

$this->title = '粉丝列表';
$this->params['breadcrumbs'][] = ['label' =>  $this->title];
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>查询</h5>
                </div>
                <div class="ibox-content">
                    <form action="" method="get" class="form-horizontal" role="form" id="form">
                        <div class="form-group">
                            <label class="col-sm-3 control-label">是否关注</label>
                            <div class="col-sm-6">
                                <div>
                                    <label><input name="follow" value="1"  type="radio" <?= $follow == 1 ? 'checked' : ''?>> 已关注 </label>
                                    <label><input name="follow" value="-1" type="radio" <?= $follow == 1 ? '' : 'checked'?>> 未关注</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">昵称/openid</label>
                            <div class="col-sm-6">
                                <div class="input-group m-b">
                                    <input type="hidden" class="form-control" name="tag_id" value="<?= $tag_id?>"/>
                                    <input type="text" class="form-control" name="keyword" value="" placeholder="<?= $keyword?>"/>
                                    <span class="input-group-btn">
                                    <button class="btn btn-white"><i class="fa fa-search"></i> 搜索</button>
                                </span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="tabs-container">
                <div class="tabs-right">
                    <ul class="nav nav-tabs">
                        <li <?php if($tag_id == ''){ ?>class="active"<?php } ?>>
                            <a href="<?= Url::to(['index'])?>"> 全部粉丝(<strong class="text-danger"><?= $all_fans ?></strong>)</a>
                        </li>
                        <?php foreach ($fansTags as $k => $tag){ ?>
                            <li <?php if($tag['id'] == $tag_id){ ?>class="active"<?php } ?>>
                                <a href="<?= Url::to(['index','tag_id' => $tag['id']])?>"> <?= $tag['name'] ?>(<strong class="text-danger"><?= $tag['count'] ?></strong>)</a>
                            </li>
                        <?php } ?>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active">
                            <div class="panel-body">
                                <div class="col-sm-12">
                                    <div class="ibox float-e-margins">
                                        <span class="btn btn-white" id="sync"> 同步选中粉丝信息</span>
                                        <span class="btn btn-white" onclick="getAllFans()"> 同步全部粉丝信息</span>
                                        <table class="table table-hover">
                                            <thead>
                                            <tr>
                                                <th><input type="checkbox" class="check-all"></th>
                                                <th>头像</th>
                                                <th>昵称</th>
                                                <th>性别</th>
                                                <th>是否关注</th>
                                                <th>关注/取消时间</th>
                                                <th>粉丝标签</th>
                                                <th>openid</th>
                                                <th>操作</th>
                                            </tr>
                                            </thead>
                                            <tbody id="list">
                                            <?php foreach($models as $model){ ?>
                                            <tr openid = "<?= $model->openid; ?>">
                                                <td><input type="checkbox" name="openid[]" value="<?= $model['openid']?>"></td>
                                                <td class="feed-element">
                                                    <img src="<?= $model->headimgurl ?>" class="img-circle">
                                                </td>
                                                <td><?= $model->nickname ?></td>
                                                <td><?= $model->sex == 1 ? '男' : '女' ?></td>
                                                <td>
                                                    <?php if($model->follow == Fans::FOLLOW_OFF){ ?>
                                                        <span class="label label-danger">已取消</span>
                                                    <?php }else{ ?>
                                                        <span class="label label-info">已关注</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if($model->follow == Fans::FOLLOW_OFF){ ?>
                                                        <?= Yii::$app->formatter->asDatetime($model->unfollowtime) ?>
                                                    <?php }else{ ?>
                                                        <?= Yii::$app->formatter->asDatetime($model->followtime) ?>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if($model['tags']){ ?>
                                                        <?php foreach ($model['tags'] as $value){ ?>
                                                            <span class="label label-success"><?= $allTag[$value['tag_id']]; ?></span>
                                                        <?php } ?>
                                                    <?php }else{ ?>
                                                        <span class="label label-default">无标签</span>
                                                    <?php } ?>
                                                    <a  href="<?= Url::to(['move-tag','fan_id' => $model->id])?>" data-toggle='modal' data-target='#ajaxModal' style="color: #0f0f0f"><i class="fa fa-sort-down"></i></a>
                                                    <select class="form-control m-b groups" style="display: none">
                                                        <?php foreach ($fansTags as $value){ ?>
                                                            <option value="<?= $value['id'] ?>" <?php if($value['id'] == $model->group_id){ ?>selected<?php } ?>><?= $value['name'] ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </td>
                                                <td><?= $model->openid ?></td>
                                                <td>
                                                    <a href="<?= Url::to(['view','id'=>$model->id])?>" data-toggle='modal' data-target='#ajaxModal'><span class="btn btn-info btn-sm">用户详情</span></a>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <?= LinkPager::widget([
                                                    'pagination'        => $pages,
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
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(".groups").change(function(){
        var group_id = $(".groups").val();
        var openid = $(this).parent().parent().attr('openid');

        $.ajax({
            type:"post",
            url:"<?= Url::to(['move-user'])?>",
            dataType: "json",
            data: {group_id:group_id,openid:openid},
            success: function(data){
                if(data.code == 404) {
                    rfAffirm(data.msg);
                }
            }
        });
    });

    // 同步所有粉丝openid
    function getAllFans() {

        rfAffirm('同步中,请不要关闭当前页面');

        $.ajax({
            type:"get",
            url:"<?= Url::to(['get-all-fans'])?>",
            dataType: "json",
            data: {},
            success: function(data){
                sync('all');
            }
        });
    }

    // 同步粉丝资料
    function sync(type,page = 0,openids = null){

        $.ajax({
            type:"post",
            url:"<?= Url::to(['sync'])?>",
            dataType: "json",
            data: {type:type,page:page,openids:openids},
            success: function(data){
                if(data.code == 200) {
                    sync(type,data.data.page);
                }else{
                    rfAffirm(data.message);
                    window.location.reload();
                }
            }
        });
    }

    // 同步选中的粉丝
    $("#sync").click(function () {
        var openids = [];
        $("#list :checkbox").each(function () {
            if(this.checked){
                var openid = $(this).val();
                openids.push(openid);
            }
        });

        sync('check',0,openids);
    });

    // 多选框选择
    $(".check-all").click(function(){
        if(this.checked){
            $("#list :checkbox").prop("checked", true);
        }else{
            $("#list :checkbox").attr("checked", false);
        }
    });
</script>