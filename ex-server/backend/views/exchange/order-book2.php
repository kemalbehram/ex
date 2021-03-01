<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;
use api\models\ExchangeCoins;

$this->title = '手续费查询';
$this->params['breadcrumbs'][] = ['label' =>  $this->title];

$market_list = ExchangeCoins::getMarketName();
?>
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>筛选条件</h5>
                </div>
                <div class="ibox-content">
                    <form action="<?= Url::to(['order_book'])?>" method="get" class="form-inline" role="form" id="form">
                        <div class="form-group">
                            <label for="" class="control-label">交易区</label>
                            <select class="form-control tpl-category-parent" name="market">
                                <option value="">全部</option>
                                <?php foreach ($market_list as $key => $value) { ?>
                                    <option value="<?= $value?>" <?= $market == $value ? "selected":'' ?>><?= $value?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="" class="control-label" style="margin-left: 15px">类型</label>
                            <select class="form-control tpl-category-parent" name="side">
                                <option value="">全部</option>
                                <!-- <option value="1">Ask</option>
                                <option value="2">Bid</option> -->
                                <option value="1" <?= $side == 1 ? "selected":''?>>卖</option>
                                <option value="2" <?= $side == 2 ? "selected":''?>>买</option>
                            </select> 
                        </div>

                        <div class="form-group">
                            <label for="" class="control-label" style="margin-left: 15px">用户id</label>
                            <input type="uid" class="form-control" name="uid" value="<?= $uid?>">
                        </div>

                        <div class="form-group">
                            <label for="" class="control-label" style="margin-left: 15px">起始条目</label>
                            <input type="number" class="form-control" name="offset" value="<?= $offset?>">
                        </div>

                        <div class="form-group" style="height: 34px">
                            <label for="" class="control-label" style="margin-left: 15px">返回条目</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="limit" value="<?= $limit?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-white"><i class="fa fa-search"></i> 搜索</button>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
                <div style="color: red;padding: 0 20px;">
                    stock手续费：<?= round($all_buy_num,4)?> money手续费：<?= round($all_sell_num,4)?>
                </div>
            </div>
        </div>
    </div>
    <!--  list begin  -->
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>订单列表</h5>
                </div>
                <div class="ibox-content">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>交易区</th>
                                <th>用户</th>
                                <th>交易类型</th>
                                <th>数量</th>
                                <th>价格</th>
                                <th>手续费</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data as $key => $value){ ?>
                                <tr>
                                    <td><?= $value['id'] ?></td>
                                    <td><?= $value['market'] ?></td>
                                    <td><?= $value['uid'] ?></td>
                                    <td><?= $value['side']==1 ? '卖' : '买' ?></td>
                                    <td><?= $value['amount'] ?></td>
                                    <td><?= $value['price'] ?></td>
                                    <td><?= $value['fee'] ?></td>
                                    <td><?= '时间：'.date('Y/m/d H:i', (int)$value['utime']) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-sm-12">
                            <?= LinkPager::widget([
                                'pagination'        => $pagination,
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