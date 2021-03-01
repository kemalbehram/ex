<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;
use jianyan\basics\common\models\wechat\CustomMenu;

$this->title = CustomMenu::$typeExplain[$type];
$this->params['breadcrumbs'][] = ['label' =>  $this->title];
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="tabs-container">
                <ul class="nav nav-tabs">
                    <?php foreach ($types as $key => $value){ ?>
                        <li <?php if($key == $type){ ?>class="active"<?php } ?>><a href="<?= Url::to(['/wechat/custom-menu/index','type' => $key])?>"> <?= $value ?></a></li>
                    <?php } ?>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="panel-body">
                            <div class="ibox float-e-margins">
                                <div class="ibox-tools">
                                    <a class="btn btn-primary btn-xs" id="getNewMenu">
                                        <i class="fa fa-cloud-download"></i>  同步菜单
                                    </a>
                                    <a class="btn btn-primary btn-xs" href="<?php echo Url::to(['edit','type'=> $type])?>">
                                        <i class="fa fa-plus"></i>  创建菜单
                                    </a>
                                </div>
                                <div class="ibox-content">
                                    <table class="table table-hover">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>标题</th>
                                            <th>显示对象</th>
                                            <th>是否在微信生效</th>
                                            <th>创建时间</th>
                                            <th>操作</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach($models as $model){ ?>
                                            <tr>
                                                <td><?php echo $model->id?></td>
                                                <td><?php echo $model->title?></td>
                                                <td>
                                                    <?php if($model->type == 1){ ?>
                                                        全部粉丝
                                                    <?php }else{ ?>
                                                        性别: <?= Yii::$app->params['individuationMenuSex'][$model->sex];?>;
                                                        手机系统: <?= Yii::$app->params['individuationMenuClientPlatformType'][$model->client_platform_type];?>;
                                                        语言: <?= Yii::$app->params['individuationMenuLanguage'][$model->language];?>;
                                                        标签: <?= empty($model->tag_id) ? '全部粉丝' : \jianyan\basics\common\models\wechat\FansTags::getTag($model->tag_id)['name'];?>;
                                                        地区: <?= empty($model->province . $model->city) ? '不限' : $model->province . $model->city;?>;
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if($model->status == 1){ ?>
                                                        <font color="green">菜单生效中</font>
                                                    <?php }else{ ?>
                                                        <a href="<?php echo Url::to(['save','id' => $model->id])?>" class="color-default">生效并置顶</a>
                                                    <?php } ?>
                                                </td>
                                                <td><?php echo Yii::$app->formatter->asDatetime($model->append)?></td>
                                                <td>

                                                    <a href="<?php echo Url::to(['edit','id'=>$model->id,'type' => $model->type])?>"><span class="btn btn-info btn-sm"><?php echo $model->type == 2 ? '查看': '编辑';?></span></a>&nbsp
                                                    <?php if($model->status == -1 || $model->type == 2){ ?>
                                                        <a href="<?php echo Url::to(['delete','id'=>$model->id,'type' => $model->type])?>" onclick="rfDelete(this);return false;"><span class="btn btn-warning btn-sm">删除</span></a>&nbsp
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <?php echo LinkPager::widget([
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

<script type="application/javascript">
    // 获取资源
    $("#getNewMenu").click(function(){
        rfAffirm('同步中,请不要关闭当前页面');
        sync();
    });

    // 同步菜单
    function sync(offset = 0,count = 20){
        $.ajax({
            type:"get",
            url:"<?= Url::to(['sync'])?>",
            dataType: "json",
            success: function(data){
                if(data.code == 200) {
                    rfAffirm(data.message);
                    window.location.reload();
                }else{
                    rfAffirm(data.message);
                }
            }
        });
    }
</script>