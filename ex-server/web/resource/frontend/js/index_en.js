function toNonExponential(num) {
    var m = num.toExponential().match(/\d(?:\.(\d*))?e([+-]\d+)/);
    return num.toFixed(Math.max(0, (m[1] || '').length - m[2]));
}
  $('body').css({
   'opacity':1
  })
  $('.coin_footer').css({
  	position:'static'
  })
 var showImg = 1;
  
			function ascBtn(){
				console.log(123)
			}
			var token = localStorage.getItem('access_token');
		
                var loginObj = {}
                function getLoginVal(type) {
                    loginObj[type] = $('#' + type).val()
                    //console.log(loginObj)
                }
                 //tool.loginStatus('',$('.headerUser'),  $('.main_bg'))
                 function loginEnter(){
                 	 var phone = tool.detectionData.checkData({
                        name: 'phone',
                        val: loginObj.phone,
                        string: '手机号'
                    })
                    var pswd = phone && tool.detectionData.checkData({
                        name: 'pwd',
                        val: loginObj.pwd,
                        string: '密码'
                    })
                    var $this = this;
                    if(phone && pswd) {
                        http.post('register/sign', {
                            mobile_phone: loginObj.phone,
                            password: loginObj.pwd
                        }, function(res) {
                            http.info(res.message)
                          	$('.person').css('display', 'block')
                            window.location.reload();
                        },function(err){
                            http.info(err.message)
                        })
                    }
                 }
                $('#goToLogin').click(function() {
                   loginEnter()
                })
                $('#pwd').keydown(function(ev){
                	ev =ev || event 
                	if(ev.keyCode==13)loginEnter();
                })
				var userInfo = null;
				var localList = null;
				var zxChance = localStorage.getItem('zxChance');
                	zxChance =zxChance && JSON.parse(zxChance);
                var userMon = null;
				   //本地用户信息；
			    //function getInfo(callBack){
			    	//sessionStorage.setItem('data-id','0');
	                /*http.post('user/user-info', {}, function(res) {
	                	//callBack && callBack(res)
	                	userInfo = res.data;
						//console.log(res.data)
						$('.uid').attr('uid', res.data['UID']).html('UID&nbsp;&nbsp;' + res.data.UID)
						sessionStorage.setItem('userInfo', JSON.stringify(res.data));
					})*/
				//}
                var $ul = $('.favorite_view')
                var data = null;
                http.createWebSocket()
                //http.init();
                var coinIndex = 0;
                  var $usdtbody = $('.tradViewContent .List_mark_new tbody');
                  var inde = 0;
                  var myselfArr = [];
				myselfArr[0] = {};
				myselfArr[0].main = 'USDT';
				myselfArr[0].list = [];
                 window.openSend = function(r){
                
              
                $ul.on('click', 'li', function(e) {
                    $('.favorite_view li').removeClass('active')
                    $(this).addClass('active')
                    var index = $(this).index();
                   	/*zxChance = localStorage.getItem('zxChance');
                	zxChance =zxChance && JSON.parse(zxChance);*/
                    if(index>0){
                    	//console.log(localList[index-1])
                       inde = index-1;
                       myselfArr[inde] = {};
                       myselfArr[inde].main = localList[index-1].main;
					   myselfArr[index-1].list = localList[index-1].list;
					   
					}
                    index - 1 >=0 && mapDom(data[index - 1], index - 1)
					if(index==0){$('.tradViewFav').css('display', 'block');$('.tradViewContent').css('display', 'none')}
					else  {$('.tradViewContent').css('display', 'block');$('.tradViewFav').css('display', 'none')}
                    coinIndex = index-1;
                    //console.log(coinIndex)
                })
		

  function mapDom(dataArr, n) {
      
      var t = 0
      if (dataArr.list.length != $('.tradViewContent  .List_mark_new tbody tr').length) {
       $('.tradViewContent  .List_mark_new tbody').empty();
      }
      dataArr && dataArr.list && dataArr.list.map(function(ele, index) {
          //console.log(ele)
          send()
          //console.log(ele.name)
          function send(){
          	 http.sendData({
                  "id": 2,
                  "method": "today.query",
                  "params": ['' + ele.name]
              })
          }
          
          window.revieceData2 = function(res) {
              t++;
//                          console.log(res)
//                          console.log('11111111')
              var rat = res.result && ((res.result.last - res.result.open) / res.result.open)
              var e = [];
              $.each(data[n].list, function(i, ev) {
                  if(!(e[i] && e[i].name)) {
                      e.push(ev)
                  }
              });
              var mon = userMon;
              if (dataArr.list.length != $('.tradViewContent  .List_mark_new tbody tr').length) {
                $('.tradViewContent .List_mark_new tbody').append(domEle(ele, res, rat, t - 1, e,mon,dataArr.main));
              }else{
                update_tradViewContent(ele, res, rat, t - 1, e,mon,dataArr.main);
              }
          }

      })
  }
  function update_tradViewContent(ele, res, rat, index, e,mon,main,monhigh,monlow,monlast) {
    //console.log(index);

      var last = res && res.result && res.result.last || '';
      var high = res && res.result && res.result.high || '';
      var low = res && res.result && res.result.low || '';
      var deal = res && res.result && res.result.deal || '';
      ele = e[index];
      rat = (rat*100).toFixed(2)
      monlast = (ele.exchange_rate_usd*last) || '';
      monhigh = (ele.exchange_rate_usd*high) || '';
      monlow = (ele.exchange_rate_usd*low) || '';
//          console.log('-----')
//                  console.log(ele);
//                  console.log(res);
//                  console.log('------')
      var status = ele && ele.status
      var stock = ele && ele.stock;
      var money = ele && ele.money;

      
//console.log(stock);
//console.log(money);
//最新价
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(2).find('.pire').html(toNonExponential(last-0));
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(2).find('.rmb').html('/ $' + (monlast-0).toFixed(2));

//24h涨跌
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(3).find('a').html(rat + '%');

//24h最高
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(5).find('.max_pire').html(toNonExponential(high-0));
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(5).find('.rmb').html('/ $' + (monhigh-0).toFixed(2));


//24h最低
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(6).find('.min_pire').html(toNonExponential(low-0));
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(6).find('.rmb').html('/ $' + (monlow-0).toFixed(2));

//24h成交量
$('.tradViewContent  .List_mark_new tbody tr').eq(index).children().eq(7).find('.price').html((deal-0).toFixed(4));
  }




			var timer1 = setInterval(function(){
					//console.log(coinIndex)
					mapDom(data[coinIndex],coinIndex)
					//10000
				},1000);
                $('.tradView:not(.tradViewFav)').on('click', '.fav_handle', function() { //添加自选
                	var d = $(this).attr('data-id');
                	
                	var $this = this;
                	//console.log(d);
                	var text = $(this).parent().siblings('.bi_icon_new').children().text();
                		if(d=='0'){
							$.each(data[coinIndex].list,function(index,res){
								
								var t =  res.stock+ '/' + res.money;
								
								if(t==text)http.post('trade/trade-add',{
									stock:res.stock,
									money:res.money
								},function(res){
									http.info('Add successful');
									$($this).attr('data-id','1').addClass('active');
									denglu();
								},function(err){
									http.info('No login');
									$($this).attr('data-id','0').removeClass('active');
								})
							})
							
						}else{
							$.each(data[coinIndex].list,function(index,res){
								var t = res.stock+ '/' + res.money;
								if(t==text)http.post('trade/trade-delete',{
									stock:res.stock,
									money:res.money
								},function(res){
									http.info('Delete successful');
									denglu();
									$($this).attr('data-id','0').removeClass('active');
									
								})
							})

						}
                })
                var favArr = [];
                $('.FavLi').click(function(){
                	 $('.tradViewFav .addChang').empty();
                	http.post('trade/trade-find',{},function(r){
                		console.log(r);
                		//domEle()
                		 favArr = r.data;
                		$.each(r.data,function(index,re){
                			 http.sendData({
		                            "id": 5,
		                            "method": "today.query",
		                            "params": [re.name]
		                        })
		                         var st = 0;
		                         
		                        window.revieceData5 = function(m){
					                	var res = m;
			                            var rat = res.result && ((res.result.last - res.result.open) / res.result.open)
			                            var e = [];
			                            $.each(r.data, function(i, ev) {
			                                if(!(e[i] && e[i].name)) {
			                                    e.push(ev)
			                                }
			                            });
			                           
			                            //	sconsole.log(e)
			                            var ele = e[st];
			                           
			                             st++;

		                                var last = res && res.result && res.result.last || '';
					                    var high = res && res.result && res.result.high || '';
					                    var low = res && res.result && res.result.low || '';
					                    var deal = res && res.result && res.result.deal || '';
					                    //var mon = (userMon*last).toFixed(2);
                                  
                                  
					                    rat = (rat*100-0).toFixed(2); 
					                $('.tradViewFav .addChang').append('<tr trade="' + ele.stock + '" currency_trade="' + ele.money + '"_"' + ele.stock + '" class="currencyList_' + ele.money + '_' + ele.stock + '">' +
			                        '<td width="2%"" style="display:none;" class="fav">' +
			                        '<b pair="' + ele.money + '_' + ele.stock + '" data-id="1" class="fav_handle '+(1==1?"active":'')+'"></b>' +
			                        '</td>' +
			                        '<td width="18%" class="bi_icon_new">' +
			                        '<a href="/trade/' + ele.money+'/'+ele.stock + '">' +
			                       
			                        '<img style="position:relative;margin-right:10px;cursor:pointer;top:5px;" src="'+ele.stock_icon+'" alt="">'+ '<span style="font-weight:600;">' +(ele.stock)+ '</span>' + '/'+ele.money+ '</a>' +
			                        '</td>' +
			                        '<td width="16%" class="rmb_pire_new">' +
			                        '<a  style="text-align: left;" href="/trade/' + ele.money+'/'+ele.stock + '">' +
			                        '<span class="pire">' + (last) +' </span>'+
			                        '</a>' +
			                        '</td>' +
			                        '<td width="8%"  style="text-align: right; class="change_rate">' +
			                        '<a href="/trade/' +ele.money+'/'+ele.stock + '" style="color:' + (rat < 0 ? "rgb(234,0,112)" : "rgb(112, 168, 0);") + ';">' + (rat) + '%</a>' +
			                        '</td>' +
			                        '<td width="100" style="display:none;"></td>' +
			                        '<td width="20%"  style="text-align:center;" class="max_pire_new">' +
			                        '<a href="/trade/' + ele.money+'/'+ele.stock + '">' +
			                        '<span class="max_pire">' + high + '</span>' +
			                        '</a>' +
			                        '</td>' +
			                        '<td width="20%" style="text-align:center;" class="min_pire_new">' +
			                        '<a href="/trade/' + ele.money+'/'+ele.stock + '">' +
			                        '<span class="min_pire">' + low + ' </span>' +
			                        '</a>' +
			                        '</td>' +
			                        '<td class="mov">' +
			                        '<a href="/trade/' + ele.money+'/'+ele.stock + '">' + ((deal-0).toFixed(2)) +ele.money +'</a>' +
			                        '</td></tr>')
					             }
                	
                		})
                	})
                })
                $('.tradViewFav .addChang').on('click','.fav_handle',function(){
                		var $this = this;
                		var text = $(this).parent().siblings('.bi_icon_new').children().text();
                		$.each(favArr,function(index,res){
								var t = res.stock + '/' + res.money;
								if(t==text)http.post('trade/trade-delete',{
									stock:res.stock,
									money:res.money
								},function(res){
									$($this).parent().parent().remove();
									$($this).attr('data-id','0').removeClass('active');
									http.info('Delete successful');
									denglu();
									coinIndex = -1;
								})
							})
                		
                })
                $('.addChang').on('click', 'a', function() {
                    var Ntext = $(this).text()
                    sessionStorage.setItem('tradeName', Ntext)
                })
        
                function domEle(ele, res, rat, index, e,mon,main,monhigh,monlow,monlast) {
                	//console.log(ele);
                    var last = res && res.result && res.result.last || '';
                    var high = res && res.result && res.result.high || '';
                    var low = res && res.result && res.result.low || '';
                    var deal = res && res.result && res.result.deal || '';
                    ele = e[index];
                    rat = (rat*100).toFixed(2)
                    monlast = (ele.exchange_rate_usd*last) || '';
                    monhigh = (ele.exchange_rate_usd*high) || '';
					monlow = (ele.exchange_rate_usd*low) || '';
//					console.log('-----')
//                  console.log(ele);
//                  console.log(res);
//                  console.log('------')
                    var status = ele && ele.status
                    var stock = ele && ele.stock;
                    var money = ele && ele.money;

                    
 					//console.log(status);
                    return('<tr trade="' + stock + '" currency_trade="' + money + '"_"' + stock + '" class="currencyList_' + money + '_' + stock + '">' +
                        '<td width="2%" class="fav">' +
                        '<b pair="' + money + '_' + stock + '" data-id="'+(status?'1':'0')+'" class="fav_handle '+(status?'active':'')+'"></b>' +
                        '</td>' +
                        '<td width="12%" class="bi_icon_new">' +
                        '<a href="/trade/' + money+'/'+stock + '">' +
                        //'<a href="javascript:;">'+
                        '<img style="position:relative;margin-right:10px;cursor:pointer;display:none;top:5px;" src="'+ele.stock_icon+'" alt="">'+ '<span style="font-weight:600;">' +(stock)+ '</span>' + '/' +money+ '</a>' +
                        '</td>' +
                        '<td width="18%" class="rmb_pire_new">' +
                        '<a  style="text-align: left;" href="/trade/' + money+'/'+stock + '">' +
                        '<span class="pire" style="color:'+(rat < 0 ? "rgb(234,0,112)":"rgb(112, 168, 0);")+';">' + ((last-0).toFixed(8)) + '</span><span class="rmb" style="color:#aaa;">/ $'+((monlast-0).toFixed(2))+' </span>' +
                        '</a>' +
                        '</td>' +
                        '<td width="8%" style="text-align: right;" class="change_rate">' +
                        '<a href="/trade/' +money+'/'+stock + '" style="color:' + (rat < 0 ? "rgb(234,0,112)" : "rgb(112, 168, 0);") + ';">' + (rat) + '%</a>' +
                        '</td>' +
                        '<td width="100" style=""></td>' +
                        '<td width="20%"  style="text-align:left;" class="max_pire_new">' +
                        '<a href="/trade/' + ele.money+'/'+ele.stock + '">' +
                        '<span class="max_pire">' + high + '</span><span class="rmb" style="color:#aaa;">/ $ '+((monhigh-0).toFixed(2))+'</span>' +
                        '</a>' +
                        '</td>' +
                        '<td width="20%" class="min_pire_new">' +
                        '<a href="/trade/' + money+'/'+stock + '">' +
                        '<span class="min_pire">' + low + '</span><span class="rmb" style="color:#aaa;">/ $'+((monlow-0).toFixed(2))+' </span>' +
                        '</a>' +
                        '</td>' +
                        '<td class="mov">' +
                        '<a href="/trade/' + money+'/'+stock + '"><span class="price" style="border: none;">' + ((deal-0).toFixed(4))+'</span><span class="rmb">&nbsp'+(money)+'</span>'+'</a>' +
                        '</td></tr>')
                }
          			/*background:url('+el.main_icon+') no-repeat;*/
				function isLogin(getToken){//登录显示是否添加自选
					//console.log(coinIndex)
	                http.post(getToken, {}, function(res) {
	                    data = res.data;
	                    //console.log(data);
	                    userMon = res.usd_to_cny
	                   	localList = data;
	                   	//console.log(coinIndex);
	                   	var  Index = coinIndex<0?0:coinIndex;
	                   	myselfArr[Index].list = data[Index].list;
						                
	                     var mon = res.usd_to_cny;
	                      $ul.children('li:not(.FavLi)').remove();
	                    data.map(function(el, index) {
	                        $ul.append('<li class="' + (index == coinIndex ? 'active' : '') + '" trade="' + el.main + '"><span class="trading"><i style="background:url('+el.main_icon+') no-repeat;background-size:100% 100%;width: 18px;height:18px;display: inline-block;margin-right: 6px;position: relative;top: 2px;"></i>'+ el.main + '</span></li>')
	                    })
	                    var n = 0;
	                    //console.log(data[0])
	                    $usdtbody.empty();
	                    
	                    data[Index] && $.each(data[Index].list, function(index, ele) {
	                        //console.log(ele)
	                        http.sendData({
	                            "id": 2,
	                            "method": "today.query",
	                            "params": ['' + ele.name]
	                        });
	                        var e = [];
	                        window.revieceData2 = function(res) {
	                           	n++;
	                          	var rat = res.result && ((res.result.last - res.result.open) / res.result.open)
	                            $.each(data[Index].list, function(i, ev) {
	                                if(!(e[i] && e[i].name)) {
	                                    e.push(ev)
	                                }
	                            });
	                            /*console.log(data[Index]);
	                            console.log(n-1);
	                            console.log(n);
	                           console.log(data[Index]);
	                           console.log('-----')
	                           console.log(n-1)
	                           console.log(e)
	                           console.log(e[n-1])*/
	                            $usdtbody.append(domEle(ele, res, rat, n -1, e,mon,data[Index].main))
	                        }
	                    })
					  //})
	                })
                	
				}
				function denglu(){
					
					isLogin('exchange/market');
               }
               denglu();
                //window.reviceMess = null;
                http.post('start/cate',{id:9,limit_begin:0,limit_num:4},function(r){
                    //console.log(r)
                    $.each(r.data, function(index,n) {
                        var timer = new Date(n.append*1000);
                            var year = timer.getFullYear();
                            var month = timer.getMonth()+1;
                            var nowDate = timer.getDate();
                            var hours = timer.getHours();
                            var min = timer.getMinutes();
                            var second = timer.getSeconds();
                            timer = year+'-'+month+'-'+nowDate+'&nbsp;'+hours+':'+min;
                       var str ='<a href="'+n.url+'" class="newBox_z z">'
                          +'<div class="newT">'+n.title+'</div>'
                          +'<div class="newIt">'+n.title+'</div>'
                          +'<div class="newTim">'+timer+'</div>'
                          +'</a>';
                      $('.newBoxAll').append(str);
                    });
                })
        }      

                 
