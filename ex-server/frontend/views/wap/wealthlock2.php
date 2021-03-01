<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="format-detection" content="telephone=no">
    <title>锁仓</title>
<style>
*{margin:0;padding:0;font-family: pingFang-SC-Regular;list-style: none;}
.main{
  padding-top: 40px;
  height: 100%;
  -webkit-box-sizing: border-box;
          box-sizing: border-box;
}
.fl{
  float: left;
}
.fr{
  float: right;
}
.list_reg{
  border-bottom: 1px solid #eee;
  padding: 10px 0;
}
.list_reg .xuhao{
  position: absolute;
  left: 0;
  width: 50px;
}
.content_detail li:nth-child(1) .xuhao {
    background: url(/resource/frontend/img/tag_bga@2x.png) no-repeat;
    background-size: 100%;
    background-position: 0 15px;
    color: #fff;
}
.content_detail li:nth-child(2) .xuhao {
    background: url(/resource/frontend/img/tag_bgb@2x.png) no-repeat;
    background-size: 100%;
    background-position: 0 15px;
    color: #fff;
}
.content_detail li:nth-child(3) .xuhao {
    background: url(/resource/frontend/img/tag_bgc@2x.png) no-repeat;
    background-size: 100%;
    background-position: 0 15px;
    color: #fff;
}
.list_reg .left{
    width: 30%;
    display: inline-block;
    text-align: left;
}
.list_reg .right{
    width: 68%;
    display: inline-block;
    text-align: left;
}
/*邀请注册列表*/
.content_detail{
  padding: 0 15px 0 15px;
  background-color: #fff;
  -webkit-box-shadow: 2px 2px 3px 0px #dedede;
          box-shadow: 2px 2px 3px 0px #dedede;
}
.content_detail li{
  width: 100%;
  background: #fff;
}
.yao_empty{
  line-height: 70px;
  font-weight: bold;
  color: #8a8ca5;
}
.oImg{
  width: 34px;
  height: 34px;
  margin: 15px 10px 5px 14px;
}
.currency_name{
  position: relative;
  top: -20px;
  font-size: 14px;
  font-weight: bold;
}
.amount_of{
  width: 25%;
  text-align: center;
}
.count{
  color: #0191f5;
  padding: 22px 0 10px 0;
  font-weight: bold;
}
.money{
  color: #8c92a8;
}
.box-all{
  padding: 20px 5%;
}
.container2{
  text-align: center;
}
.container2 .yao_title{
  padding: 30px 0 20px 0;
}
.container2 .footer{
  width: 100%;
  position: relative;
  border-bottom: 1px solid #eeedf4;
}
.container2 .footer .item{
  width: 50%;
  height: 100%;
  font-size: 16px;
  color: #212529;
  text-align: center;
  background: #fff;
  position: absolute;
  top: 0;
}
.container2 .footer .item .detail{
  position: absolute;
  width: 80px;
  left: 50%;
  margin: 0 0 0 -40px;
  line-height: 48px;
}
.container2 .footer .item .detail span{
  border: 1px;
}
.container2 .footer .item.active{
  color: #335bdc;
}
.container2 .footer .item.active .detail{
  border-bottom: 2px solid #335bdc;
}
.container2 .footer .item.right{
  right: 0;
}
.fb{
  font-weight: bold;
}
.h40{
  height: 40px;
}
.l50{
  line-height: 50px;
}
.f20{
  font-size: 20px;
  font-weight: bold;
  line-height: 50px;
}
.f18{
  font-size: 18px;
}
.f16{
  font-size: 16px;
}
.f14{
  font-size: 14px;
}
.f12{
  font-size: 12px;
}
.container{
  padding: 10px;
  background-color: #474956;
  border-radius: 10px;
  text-align: center;
  color: #fff;
}
.container .title{
  padding: 20px 0 0 0;
  color: #ffe6ac;
}
.container .num{
  color: #ffe6ac;
  font-size: 20px;
  font-weight: bold;
}
.container hr{
  margin: 2px 0;
  height: 1px;
  border: 0px;
  border-top: 1px solid #666875;
  padding: 0px;
  overflow: hidden;
  font-size: 0px;
}
.container .top,
.container .bottom{
  padding: 0 !important;
  line-height: 25px;
  height: 50px;
  width: 100%;
  position: relative;
  font-size: 20px;
}
.container .top .left,
.container .bottom .left{
  width: 30%;
  position: absolute;
  left: 0;
  top: 0;
  text-align: center;
}
.container .top .middle,
.container .bottom .middle{
  width: 65%;
  position: absolute;
  left: 35%;
  top: 0;
  text-align: center;
}
.container .top .right,
.container .bottom .right{
  width: 30%;
  position: absolute;
  right: 0;
  top: 0;
  text-align: center;
}
</style>
</head>
<body>
    <div class="box-all">


      <div class="container2">
        <div class="f18 yao_title fb">我的锁仓</div>
        <div class="footer">
        </div>
        <ul class="content_detail myweath">
          <li v-for="val in regListInfo">
            <div class="list_reg f14">
              <span class="left">币种</span>
              <span class="right">详情</span>
            </div>
          </li>
        </ul>
      </div>

      <div class="container2">
        <div class="f18 yao_title fb">解冻记录</div>
        <div class="footer">
        </div>
        <ul class="content_detail weathprofit">
          <!-- <div class="yao_empty f14"  ref="block_list">暂时没有邀请哦</div> -->
          <li v-for="val in regListInfo">
            <div class="list_reg f14">
              <span class="left">时间</span>
              <span class="right">币种/数量/详情</span>
            </div>
          </li>
        </ul>
      </div>




    </div>
    <script type="text/javascript" src="/resource/frontend/js/jquery.min.js"></script>
<script type="text/javascript">
function toNonExponential(num) {
    num = num - 0;
    var m = num.toExponential().match(/\d(?:\.(\d*))?e([+-]\d+)/);
    return num.toFixed(Math.max(0, (m[1] || '').length - m[2]));
}
var token = '<?php echo $token; ?>';
var post_data={
  access_token: token,
  chain_network: 'main_network',
  os: 'web',
}


$.ajax({
   type: 'POST',
   url: '/api/user/myweath2',
   dataType: 'json',
   data: post_data,
   success: function(data) {
            if(data.code == 200){
              List = data.data;
              var rank_index = 0;
              var type_name;

              $.each(List, function(index,r) {
                   rank_index = index +1 ;
                   if(r.type==1){
                      type_name = "按月释放";
                      $('.myweath').append('<li v-for="val in regListInfo"><div class="list_reg f14"><span class="left">'+ r.coin_symbol +'</span> <span class="right">总量：'+r.amount+' - 剩余'+ r.left_amount +' - '+ type_name +'</span></div></li>');
                   }else{
                      type_name = "按条件释放";
                      $('.myweath').append('<li v-for="val in regListInfo"><div class="list_reg f14"><span class="left">'+ r.coin_symbol +'</span> <span class="right">总量：'+r.amount+' - 剩余'+ r.left_amount +' - '+ type_name +'</span></div></li>');
                   }                   
              });
            }else{
                console.log(data.descrp);
            }
    }
});

$.ajax({
   type: 'POST',
   url: '/api/user/weathprofit2',
   dataType: 'json',
   data: post_data,
   success: function(data) {
            if(data.code == 200){
              List = data.data;
              var rank_index = 0;
              $.each(List, function(index,r) {
                   rank_index = index +1 ;
                   $('.weathprofit').append('<li v-for="val in regListInfo"><div class="list_reg f14"><span class="left">'+ r.log_time +'</span> <span class="right">'+r.coin_symbol+' - '+ r.amount+' - '+ r.memo+'</span></div></li>');
              });
            }else{
                console.log(data.descrp);
            }
    }
});


</script>
</body>
</html>