<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;
use jianyan\basics\common\models\wechat\RuleKeyword;
use common\enums\StatusEnum;

$this->title = '自动回复';
$this->params['breadcrumbs'][] = ['label' =>  $this->title];
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="tabs-container">
                <ul class="nav nav-tabs">
                    <?= $this->render('/common/rule-nav',[
                        'nav_type' => 1,
                    ])?>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="panel-body">
                            <div class="ibox float-e-margins">
                                <div class="row">
                                    <div class="col-sm-3">
                                        <form action="" method="get" class="form-horizontal" role="form" id="form">
                                            <div class="input-group m-b">
                                                <input type="text" class="form-control" name="keyword" value="" placeholder="<?= $keyword ? $keyword : '请输入规则名称'?>"/>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-white"><i class="fa fa-search"></i> 搜索</button>
                                                </span>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="ibox-tools">
                                            <div class="input-group m-b">
                                                <div class="input-group-btn">
                                                    <button tabindex="-1" class="btn btn-white" type="button">添加回复</button>
                                                    <button data-toggle="dropdown" class="btn btn-primary dropdown-toggle" type="button"><span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <?php foreach ($modules as $key => $mo){ ?>
                                                            <li><a href="<?= Url::to(['reply-'.$key.'/edit'])?>"><?= $mo?></a></li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="ibox-content">
                                    <div class="btn-group">
                                        <a class="btn <?= !$module ? 'btn-primary': 'btn-white' ;?>" href="<?= Url::to(['index'])?>">全部</a>
                                        <?php foreach ($modules as $key => $mo){ ?>
                                            <a class="btn <?php echo $module == $key ? 'btn-primary': 'btn-white' ;?>" href="<?= Url::to(['index','module'=>$key])?>"><?= $mo?></a>
                                        <?php } ?>
                                    </div>
                                    <div class="hr-line-dashed"></div>
                                    <?php foreach($models as $model){ ?>
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <span class="collapsed"><?= $model->name ?></span>
                                                <span class="pull-right" id="<?= $model->id ?>">
                                                    <span class="label label-info">优先级：<?= $model->displayorder; ?></span>
                                                   <?php if(RuleKeyword::verifyTake($model->ruleKeyword)){ ?>
                                                       <?php if($model->status == StatusEnum::ENABLED){ ?>
                                                           <span class="label label-info">直接接管</span>
                                                       <?php } ?>
                                                   <?php } ?>
                                                    <?php if($model->status == StatusEnum::DELETE){ ?>
                                                        <span class="label label-danger" onclick="statusRule(this)">已禁用</span>
                                                    <?php }else{ ?>
                                                        <span class="label label-info" onclick="statusRule(this)">已启用</span>
                                                    <?php } ?>
                                                </span>
                                            </div>
                                            <div id="collapseOne" class="panel-collapse collapse in" aria-expanded="true" style="">
                                                <div class="panel-body">
                                                    <div class="col-lg-9 tooltip-demo">
                                                    <?php if($model->ruleKeyword){ ?>
                                                        <?php foreach($model->ruleKeyword as $rule){
                                                            if($rule->type != RuleKeyword::TYPE_TAKE){ ?>
                                                                <span class="simple_tag" data-toggle="tooltip" data-placement="bottom" title="<?= RuleKeyword::$typeExplain[$rule->type]; ?>"><?= $rule->content?></span>
                                                            <?php }
                                                        }
                                                    } ?>
                                                    </div>
                                                    <div class="col-lg-3">
                                                        <div class="btn-group pull-right">
                                                            <a class="btn btn-white btn-sm" href="<?= Url::to(['reply-'.$model->module.'/edit','id'=>$model->id])?>"><i class="fa fa-edit"></i> 编辑</a>
                                                            <a class="btn btn-white btn-sm" href="<?= Url::to(['delete','id'=>$model->id])?>" onclick="rfDelete(this);return false;"><i class="fa fa-times"></i> 删除</a>
<!--                                                            <a class="btn btn-white btn-sm" href="#"><i class="fa fa-bar-chart-o"></i> 使用率走势</a>-->
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <?= LinkPager::widget([
                                                'pagination' => $pages,
                                                'maxButtonCount' => 5,
                                                'firstPageLabel' => "首页",
                                                'lastPageLabel' => "尾页",
                                                'nextPageLabel' => "下一页",
                                                'prevPageLabel' => "上一页",
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
<script type="text/javascript">
    // status => 1:启用;-1禁用;
    function statusRule(obj){

        var id = $(obj).parent().attr('id');
        var self = $(obj);
        var status = self.hasClass("label-danger") ? 1 : -1;

        $.ajax({
            type:"get",
            url:"<?= Url::to(['ajax-update'])?>",
            dataType: "json",
            data: {id:id,status:status},
            success: function(data){
                if(data.code == 200) {
                    if(self.hasClass("label-danger")){
                        self.removeClass("label-danger").addClass("label-info");
                        self.text('已启用');
                    } else {
                        self.removeClass("label-info").addClass("label-danger");
                        self.text('已禁用');
                    }
                }else{
                    alert(data.message);
                }
            }
        });
    }
</script>