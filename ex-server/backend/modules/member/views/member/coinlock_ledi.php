<?php
use yii\widgets\ActiveForm;


?>
<style>
    .field-coins-ram_token_addr{
        display: none;
    }
</style>
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>锁仓</h5>
                </div>
                <div class="ibox-content">
<form id="w0" action="/backend/member/member/coinlock_post_ledi" method="post">
                    <div class="col-sm-12">


<div class="form-group field-member-type">
<label class="control-label" for="member-type">用户id：<?php echo $user['id']; ?></label>
<input type="hidden" name="uid" value="<?php echo $user['id']; ?>">
<input type="hidden" name="coin_symbol" value="<?php echo $_GET['coin_symbol']; ?>">
<div class="help-block"></div>
</div>


<div class="form-group field-member-type">
<label class="control-label" for="member-type">用户名：<?php echo $user['username']; ?></label>
<div class="help-block"></div>
</div>

<div class="form-group field-member-type">
<label class="control-label" for="member-type">币种：<?php echo $_GET['coin_symbol']; ?></label>
<div class="help-block"></div>
</div>

<div class="form-group field-member-type">
<label class="control-label" for="member-type">余额：<?php echo $balance; ?></label>
<div class="help-block"></div>
</div>

<div class="form-group field-member-type">
<label class="control-label" for="member-type">当前LDGC价格：</label>
<input type="text" id="ldgc_price" class="form-control" name="ldgc_price" value="<?php echo $ldgc_price; ?>" aria-required="true" aria-invalid="false" >
<div class="help-block"></div>
</div>


<div class="form-group field-member-type">
<label class="control-label" for="member-type">加币数量(USDT)：</label>
<input type="text" id="usdt_num" class="form-control" name="usdt_num" placeholder="请输入加币数量(USDT)" value="" aria-required="true" aria-invalid="false" oninput="update_coin()">
<div class="help-block"></div>
</div>

<div class="form-group field-member-type">
<label class="control-label" for="member-type">折合LDGC：</label>
<input type="text" class="form-control ldgc_num" name="" value="" aria-required="true" aria-invalid="false" disabled="">
<input type="hidden" class="form-control ldgc_num" name="ldgc_num" value="" aria-required="true" aria-invalid="false">
<div class="help-block"></div>
</div>


<div class="form-group field-member-coin_symbol">
<label class="control-label" for="member-coin_symbol">锁仓套餐</label>
<select id="member-coin_symbol" class="form-control" name="wealth_package_id" value="5">
    <option value="0">请选择套餐</option>
    <?php foreach($wealth_package as $key => $vo): ?>
        <option value="<?php echo $vo['id']; ?>"><?php echo $vo['name']; ?> - 周期<?php echo $vo['period']; ?>天 - 释放<?php echo $vo['day_profit']; ?>%</option>
    <?php endforeach; ?>
</select>
<div class="help-block"></div>
</div>

                        <div class="hr-line-dashed"></div>


                    </div>

                    <div class="form-group">
                        <div class="col-sm-12 text-center">
                            <button class="btn btn-primary" type="submit">保存内容</button>
                            <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

    $(function () {
        var val = $("input[name='Coins[ram_status]']:checked").val()
        if(val == 1){
            $(".field-coins-ram_token_addr").css('display','block')
        }
        $("input[name='Coins[ram_status]']").click(function () {
            if($(this).val() == 0){
                $(".field-coins-ram_token_addr").css('display','none')
            }else{
                $(".field-coins-ram_token_addr").css('display','block')
            }
        })
    })

function update_coin(){
    var ldgc_price = $('#ldgc_price').val();
    if (ldgc_price < 0.001) {
        alert('LDGC价格有误,请先手动填写LDGC价格');
        $('#usdt_num').val('');
        return;
    }
    var usdt_num = $('#usdt_num').val();
    var ldgc_num = $('.ldgc_num').val(number_format(usdt_num/ldgc_price,4));
}

function number_format(num) {
    return num.toFixed(4);
}

function number_format2(number, decimals, dec_point, thousands_sep, roundtag) {
    // number = (number + '').replace(/[^0-9+-Ee.]/g, '');
    // roundtag = roundtag || "round"; //"ceil","floor","round"
    // var n = !isFinite(+number) ? 0 : +number,
    //     prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    //     sep = (typeof thousands_sep === 'undefined') ? ' ' : thousands_sep,
    //     dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    //     s = '',
    //     toFixedFix = function (n, prec) {
 
    //         var k = Math.pow(10, prec);
    //         console.log();
 
    //         return '' + parseFloat(Math[roundtag](parseFloat((n * k).toFixed(prec*2))).toFixed(prec*2)) / k;
    //     };
    // s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
    // var re = /(-?\d+)(\d{3})/;
    // while (re.test(s[0])) {
    //     s[0] = s[0].replace(re, "$1" + sep + "$2");
    // }
 
    // if ((s[1] || '').length < prec) {
    //     s[1] = s[1] || '';
    //     s[1] += new Array(prec - s[1].length + 1).join('0');
    // }
    // return s.join(dec);
}


</script>