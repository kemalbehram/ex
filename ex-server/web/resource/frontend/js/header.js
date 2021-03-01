
        $(function() {
            tool.loginStatus($('.loginArea'), $('.person'))
            bindEvent();
            var userInfo = sessionStorage.getItem('userInfo')
            userInfo = userInfo && JSON.parse(userInfo);
            userInfo && $('.uid').append(userInfo.UID)
            function bindEvent() {

                $(".headerUser").find('.asset_header_hover').hover(function() {
                    var $headUser = $(".headerUser"), $mw = $('.mywallet'), $mwv = $('.mywalletView');
                    $headUser.find('.uses').find('.arrow').addClass('active');
                    $mw.show();
                    $mwv.show();
                }, function() {
                    var $headUser = $(".headerUser"), $mw = $('.mywallet'), $mwv = $('.mywalletView');

                    $mw.hide();
                    $mwv.hide();
                    $headUser.find('.uses').find('.arrow').removeClass('active');
                });

                $('.mywalletView').hover(function() {
                    $(".mywallet").show();
                    $('.mywalletView').show();
                    $('.headerUser').find('.uses').find('.arrow').addClass('active');
                }, function() {
                    $(".mywallet").hide();
                    $('.mywalletView').hide();
                    $('.headerUser').find('.uses').find('.arrow').removeClass('active');
                });

                /* 消息 */
                $(".headerUser").find('.msging').hover(function() {
                    $(".headerUser").find('.myMsgView').show();
                    $(".headerUser").find('.msgView').show();
                }, function() {
                    $(".headerUser").find('.myMsgView').hide();
                    $(".headerUser").find('.msgView').hide();
                });

                /*个人中心*/
                $(".headerUser").find('.asset_headerHover').hover(function() {
                    $(".headerUser").find('.myuserView').show();
                    $(".headerUser").find('.myUser').show();
                },function(){
                    $(".headerUser").find('.myuserView').hide();
                    $(".headerUser").find('.myUser').hide();
                })

                $('.myuserView').hover(function(){
                    $(".headerUser").find('.myuserView').show();
                    $(".headerUser").find('.myUser').show();
                },function(){
                    $(".headerUser").find('.myuserView').hide();
                    $(".headerUser").find('.myUser').hide();
                })

            }
            var tradeName = sessionStorage.getItem('tradeName')

            //$('.trading_nav a').attr('href', '/trade?changeName=' + (tradeName ? tradeName : 'BTCUSDT'))
            var userInfo = sessionStorage.getItem('userInfo');
            userInfo = userInfo && JSON.parse(userInfo);

            var h = window.location.href;
            var ha = window.location.hash;
          
            //ha==1,百科；ha==0,公告；
           
            //console.log(/recharge/g.test(h))
            // $('.nav li').find('a').removeClass('active').eq(0).addClass('active')
            // if(/wiki/g.test(h)){//百科
            //     $('.nav li').find('a').removeClass('active').eq(3).addClass('active')
                
            // }else if(/sellbtc/g.test(h)){
                
            //     $('.nav li').find('a').removeClass('active').eq(2).addClass('active');
            //     $('.navLift li').find('a').removeClass('active')
            //     $('.sell').addClass('active');

            // }else if(/recent/g.test(h)){//公告
            //     $('#gonggao').click(function(){
            //         window.location.reload();
            //     })
            //     $('.nav li').find('a').removeClass('active').eq(4).addClass('active')
                
            // }else if(/trade/g.test(h)){//交易
            //     $('.nav li').find('a').removeClass('active').eq(1).addClass('active')
            // }else if(/help/g.test(h)){//help
            //     $('.nav li').find('a').removeClass('active').eq(5).addClass('active')
            // }else if(/deploy/g.test(h)){//deploy
            //     $('.nav li').find('a').removeClass('active').eq(6).addClass('active')
            // }else if(/recharge/g.test(h)){
            //     $('.nav li').find('a').removeClass('active');
            //     $('.navLift li').find('a').removeClass('active').eq(0).addClass('active');
            // }else if(/withdraw/g.test(h)){
            //     $('.nav li').find('a').removeClass('active');
            //     $('.navLift li').find('a').removeClass('active').eq(1).addClass('active');

            // }else if(/assets/g.test(h)){
            //     $('.nav li').find('a').removeClass('active');
            //     $('.navLift li').find('a').removeClass('active').eq(2).addClass('active');

            // }else if(/message/g.test(h)){
            //     $('.nav li').find('a').removeClass('active');
            //     $('.navLift li').find('a').removeClass('active')
            //     $('.news').addClass('active');

            // }else if(/uc/g.test(h)){

            //     $('.nav li').find('a').removeClass('active');
            //     $('.navLift li').find('a').removeClass('active')
            //     $('.asset_headerHover').addClass('active');
            // }
            //  if(/0/.test(ha)){
            // 	//console.log('公告')
            //     $('.nav li').find('a').removeClass('active').eq(4).addClass('active')
            //  }else if(/1/.test(ha)){
            // 	//console.log('百科')
            //     $('.nav li').find('a').removeClass('active').eq(3).addClass('active')
            // }else if(/6/.test(ha)){
            // 	    $('.nav li').find('a').removeClass('active')
            // }

           
		});




$(function(){
    var ind = 1;//第一次未进入请求
    $('#getAsset').on('mouseenter',function(){
        if(ind == 1)http.post('bargain/balance',{},function(res){
            console.log(res)
                        $('.totalAssets').html('≈NT$'+((res.data.total_money*6.88).toFixed(2)));
                        ind=0;
                });
    })

    http.post('user/user-info',{},function(re){
        console.log(re)
        if (re.code == 200) {
            $('.uid').append(re.data.UID).attr('uid',re.data.UID)
        }
    })

    http.post('user/message-list',{type:1,limit_begin:0,limit_num:5},function(res){
        console.log(res);
        $('.msgNnm').css('display','block').html(res.count).attr('num',res.count);
        $('.msgBlock').css('display','none');
        $('.msgAll').css('display','block');
        $.each(res.data, function(index,re) {
            $('#useMessage').append('<li class="msglist" >'+
                        '<a href="#">'+re.title+'</a>'+
                        '<i>'+re.add_time+'</i>'+
                    '</li>')
        });
    })
})
