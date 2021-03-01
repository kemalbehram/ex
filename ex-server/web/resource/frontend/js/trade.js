
function toNonExponential(num) {
    var m = num.toExponential().match(/\d(?:\.(\d*))?e([+-]\d+)/);
    return num.toFixed(Math.max(0, (m[1] || '').length - m[2]));
}
//console.log(1111111);
window.openSend = function(r){
	var changeName = stock + '' +money;
	var buymoney = null;
	var utc = null; //当前主交易市场人民币汇率
	var nowAsk = null;
	var nowBids = null;

	function send1(){
		http.sendData({
			"id": 1,
			"method": "today.query",
			"params": [changeName]
		})
	}
	send1();

	$('.headerPrice').append('('+money+')');
	$('.headerNum').append('('+stock+')')
    
	
	$('.sell_record .unit').empty().append(money)
	$('.sell_num .unit').empty().append(stock)
	$('.buy_record .unit').empty().append(money)
	$('#trustbtnout').empty().append("卖出&nbsp;"+stock)
	$('#trustbtnin').empty().append("买入&nbsp;"+stock)
	$('.buy_num .unit').empty().append(stock)
	var exchangeD = null;
			
window.revieceData1 = function(res) {
	if(res.error!==null){
		console.log('交易市场不存在！')
	}
	$('.total_top_new').empty();
	var d = res && res.result;
	var last = d && d.last || '--';
	var deal = d && d.deal || '--';
	var rat = d && ((d.last - d.open) / d.open);
	var high = d && d.high || '--';
	var low = d && d.low || '--';
	rat = (rat*100).toFixed(2)
	var icon =exchangeD && exchangeD.stock_icon;
	//console.log(icon);
	var cna = stock+'/'+money;
	//console.log(exchangeD);
    var m1 =money
    var s1 =stock
    $('.new_price1').empty().append('<div>'+'<span class="new_price1" style="color:'+(rat<0?"#e01307":"#13920f")+'">'+ toNonExponential(last-0) +'</span>'+'</div>')

    if (exchangeD == null) {
    	var now_exchange_rate_cny = 0;
    }else{
    	//console.log(exchangeD);
    	var now_exchange_rate_cny = exchangeD.exchange_rate_cny;
    }
	var mon = now_exchange_rate_cny;
  
   $('.shiftRmb1').empty().append('/NT$'+((mon*last-0).toFixed(4)))
  
	$('.total_top_new').append('<div class="coin_coin">' +
		'<span class="middle"></span>' +
		'<img src="'+icon+'">' +
		'</div><div class="massg">' +
		'<p><span class="currency_mark" style="color:#3d3dce;">' + cna + '</span><span class="new_price">' + toNonExponential(last-0) + '</span></p>' +
		'<p><span class="shiftRmb">NT$'+((mon*last-0).toFixed(4))+'</span></p>' +
		'<p><span><font data-i18n="b_1">成交</font>&nbsp;<em id="24h_count" style="font-style: initial;">' + ((deal*1).toFixed(2)) +(m1) + '</em>&nbsp;</span><span class="rmbPrice"><i class="'+(rat<0?"active":"")+'"></i><em id="rmbPrice" style="color:'+(rat<0?"#eb6153" : "#77BA77")+'">' + (rat + '%') + '</em></span></p>' +
		'<p><span><font data-i18n="b_2" color="#999">最高</font>&nbsp;<em id="24h_max" style="font-style: initial;">' + high + '</em></span><span><font data-i18n="b_3">最低</font>&nbsp;<em id="24h_min">' + low + '</em></span></p>' +
		'</div>'
	)
	//$(".curList_new").animate({ scrollTop: $(".curList_new").scrollTop() + $('.curList_new tr:last').offset().top - $(".curList_new").offset().top }, 1000);
}
            
http.post('bargain/balance', {//余额
	asset_type: stock + '|' + money
}, function(res) {
	$('.balance_view tbody').empty();
	$.each(res.data && res.data.list, function(m, ev) {
			if(ev.name==money){//计算最大买入量
				var p = $('#coinpricein_new').val()
				var t = (ev.available/p).toFixed(4);
				//console.log();
				$('.sell_num .mainUnit i').html(t)
				buymoney = ev.available;
			}
			if(ev.name==stock){
              	var a = nowBids&&(nowBids[0][0] ? nowBids[0][0] : 0)||'0';
				var num2 =  nowBids&&(nowBids[0][1] ? nowBids[0][1] : 0)||'0';
					$('#coinpriceout_new').val(a)									
				if(num2-0>ev.available-0)num2 = ev.available;
				$('#numberout_new').val(num2);
				$('#coinpriceAmount_new').val(num2*a);
				$('.buy_num .mainUnit i').html(ev.available);
			}
		$('.balance_view tbody').append(
			'<tr height="24" class="">' +
			'<td class="currency_mark" width="72">' + ev.name + '</td>' +
			'<td class="total" width="134">' + ev.available + '</td>' +
			'<td class="available balance" width="154">' + ev.available + '</td>' +
			'</tr>'
		)
	})

})

function domElement(data, result, index, e) {
	var deal = result.result && result.result.last || '--';
	var rat = result.result && (((result.result.last - result.result.open) / result.result.open) * 100).toFixed(2)
	
	data = e[index - 1]
	if(data&&data.name){
	return(
		'<tr currency_trade="USDT_BTC" class="currencyList_USDT_BTC ' + (index == 0 ? 'active' : "") + '" trade="BTC">' +
		'<td width="60px" class="bi_icon_new" stlye="text-align:center;">' +
		'<a href="/trade/' + data.money+'/'+data.stock+'"style="font-weight:500;font-size:100%;" >'+ '<span style="font-weight:600;">' +(data.stock)+ '</span>' +'/'+data.money +
		'</a>' +
		'</td>' +
		'<td width="50px" class="rmb_pire_new">' +
		'<a href="/trade/'+ data.money+'/'+data.stock+  '"style="color:#666;font-weight: 500;">' + deal + '</a>' +
		'</td>' +
		'<td width="30px" class="change_rate">' +
		'<a href="/trade/' + data.money+'/'+data.stock+   '" style="color:'+(rat<0?"#e62512" : "rgb(65, 155, 1);")+'">' + (rat == null ? '--' : rat+'%') + '</a>' +
		'</td>' +
		'<td width="18px" class="fav">' +
		'<b pair="USDT_BTC" class="fav_handle"></b>' +
		'</td>' +
		'</tr>'
	)
	}
}
  
var ascData = {'ascValue':'zone_sort','status':'init'};// 排序参数
var FavData = "";// 原始行情数据
var curList = [];//当前的交易币种
function ascDOM(data){
	var newData = {};//新的数组
    if(ascData.status == 'asc'){
      Object.keys(data).forEach(function (key,index){
      	newData[key] = data[key].sort(util.sort.asc(ascData.ascValue)) ;
      })
    }else if(ascData.status == 'des'){
      Object.keys(data).forEach(function (key,index){
      	newData[key] = data[key].sort(util.sort.des(ascData.ascValue)) ;
      })
    }else if(ascData.status == 'init'){
      Object.keys(data).forEach(function (key,index){
      	newData[key] = data[key].sort(util.sort.asc('zone_sort'));
      })
    }

    return newData;
}

    // 行情交易排序
function ascBtn(self){

    if($(self).attr('type') == 'init'){
        /*默认des*/
        $(self).attr('type','des');
        ascData.status = 'des'
        $('.tradeNav').find('ul').find('li .amp span i').removeClass('active');
        $(self).find('.amp .des i').addClass('active');
        
    }else if($(self).attr('type') == 'asc'){
        $(self).attr('type','init');
        ascData.status = 'init'
        $('.tradeNav').find('ul').find('li .amp span i').removeClass('active');
    }else if($(self).attr('type') == 'des'){
        $(self).attr('type','asc');
        ascData.status = 'asc'
        $('.tradeNav').find('ul').find('li .amp span i').removeClass('active');
        $(self).find('.amp .asc i').addClass('active');
    }

    ascData.ascValue = $(self).attr('ascvalue');

    ascDOM(FavData);

}  
/*搜索*/
function OrderSearch(key,obj){
    var objView = $('.curView_New').find('.tradViewSearch');;

    obj.val(key.replace(/[^a-zA-Z0-9]/g,''));
    var a =1;
    util.searchReg(obj.val(),curList,function(data){
        if(data!=''){
            objView.find('.tradView tbody tr').hide();
            data.forEach(function(key,index){
                objView.find('.tradView tbody tr').each(function(i,k){
                    if(key==$(k).attr('trade')){
                        $(k).show();
                    }
                })
            })
        }else if(obj.val() !=''){
            objView.find('.tradView tbody tr').hide();
        }else{
            objView.find('.tradView tbody tr').show();
        }
        
    })
}
			
			//自选功能；
$('#favorite_view').on('click','.FavLi',function(){
	$('.tradViewFav  tbody').empty();
	http.post('trade/trade-find',{},function(r){
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
	                    var mon = utc*last;
	                    rat = ((rat-0)*100).toFixed(2); 
	                    //console.log(rat.toFixed(4))
	                $('.tradViewFav  tbody').append('<tr trade="' + ele.stock + '" currency_trade="' + ele.money + '"_"' + ele.stock + '" class="currencyList_' + ele.money + '_' + ele.stock + '">' +
	                
	                '<td width="60px" class="bi_icon_new" >' +
	                '<a href="/trade/' + ele.money+'/'+ele.stock + '" style="width:80px;">' +
	               
	                '<img src="'+ele.stock_icon+'" alt="'+ele.stock+'" style="margin-left:0px;">'+ '<span style="font-weight:600;">' +(ele.stock)+ '</span>' + '/'+ele.money+ '</a>' +
	                '</td>' +
	                '<td width="50px" class="rmb_pire_new">' +
	                '<a href="/trade/' + ele.money+'/'+ele.stock + '" style="width:50px;">' +
	                '<span class="pire">' + (last) + 
	                '</a>' +
	                '</td>' +
	               '<td width="30px" class="rmb_pire_new">' +
	                '<a href="/trade/' + ele.money+'/'+ele.stock + '" style="width:50px;color:'+(rat<0?"#e62512" : "rgb(65, 155, 1);")+'">' + (rat == null ? '--' : rat+'%') + 
	                '</a>' +
	               '</td></tr>')
	             }
		})
	})
})
var indexList = null;
var allList = null;
function get_market_info(market_url){
		http.post(market_url, {}, function(res) {
		//console.log(res)
		allList = res.data;

		$.each(res.data,function(i,n){
			$.each(n.list,function(x,y){
				if(y.name==changeName){
					exchangeD = n.list[x];
					$('.coin_coin img').attr('src',y.stock_icon);
				}
			})
		})
		//console.log('获取市场信息');
		//当前交易市场cny价格
		utc = exchangeD.exchange_rate_cny;
		send1();
		//填充sell or buy 金额；
		//var b = $('#sellCoin tr:last-child td.priceNum span').html();
		var b =nowAsk && nowAsk[nowAsk.length-1] && nowAsk[nowAsk.length-1][0];
		var num1 =nowAsk && nowAsk[nowAsk.length-1] && nowAsk[nowAsk.length-1][1];
		
		//ar num1 = $('#sellCoin tr:last-child td:nth-child(3)').html();
		$('#coinpricein_new').val(b);
		var mon = res.usd_to_cny*b
		var monprice = (mon-0).toFixed(2) != 'NaN' ?(mon-0).toFixed(2):''
		$('.buy .unit2 i').html('≈NT$'+monprice);
		var buyMax = $('.sell_num .mainUnit i').html();
		var numN = 0
		if(buyMax-0>num1-0)numN = num1;
		else numN = buyMax;
		numN = numN !='NaN'?numN:''
		$('#numberin_new').val((numN-0).toFixed(8));
		var nN = window.isNaN(numN*b)?'':numN*b; 
		//console.log(numN*b)
		$('#coinAmount_new').val(nN);
		var m = utc*b;
		var mpri = (m-0).toFixed(2) !='NaN'?(m-0).toFixed(2):''
		//console.log(mpri)
		$('.sell .unit2 i').html('≈NT$'+mpri);
		$('#favorite_view').empty().append('<li trade="Fav" class="FavLi"><span class="collection"><font data-i18n="a_optional">自选</font></span></li>')

		res.data.map(function(r, index) {
			$('#favorite_view').append('<li trade="' + r.main + '" class=' + (r.main == money ? 'active' : '') + '><span class="trading' + r.main + '">' + r.main + '</span></li>')

			if(r.main == money)indexList = r.list;
			//if(index == 0)indexList = r.list;
			
		})
			var i = 0;
		$.each(indexList, function(index, ev) {
			http.sendData({
				"id": 2,
				"method": "today.query",
				"params": [ev.name]
			})

			var e = [];

			window.revieceData2 = function(es) {
				i++;
				$.each(indexList, function(i, ev) {
					if(!(e[i] && e[i].name)) {
						e.push(ev)
					}
				});
				//console.log(indexList);
				//console.log(ev)
				$('.tradViewContent .curList_new .List_mark_new tbody').append(domElement(ev, es, i, e))
				//$(".curList_new").animate({ scrollTop: $(".curList_new").scrollTop() + $('.curList_new tr:last').offset().top - $(".curList_new").offset().top }, 1000);
				
			}

		})
	})
}
get_market_info('exchange/market');
			
function mapWebSco(dataList, type) {
	if (dataList.length != $('.tradViewContent .curList_new .List_mark_new tbody tr').length) {
		$('.tradViewContent .curList_new .List_mark_new tbody').empty()
	}
	dataList && dataList.map(function(r, index) {
		http.sendData({
			"id": 2,
			"method": "today.query",
			"params": [r.name]
			})
		var e = [];
		var n = 0;
		window.revieceData2 = function(es) {
			//console.log(es)
			n++;
			$.each(dataList, function(i, ev) {
				if(!(e[i] && e[i].name)) {
					e.push(ev)
				}
			});
		//console.log('mmmmmmmmmmmmmmm' + n);

			if (dataList.length != $('.tradViewContent .curList_new .List_mark_new tbody tr').length) {
				$('.tradViewContent .curList_new .List_mark_new tbody').append(domElement(r, es, n, dataList));
			}else{
				update_tradViewContent(r, es, n, dataList);
			}
		}

	})
}
function update_tradViewContent(data, result, index, e) {

	var deal = result.result && result.result.last || '--';
	var rat = result.result && (((result.result.last - result.result.open) / result.result.open) * 100).toFixed(2)
	data = e[index - 1]

	//}
	if(data&&data.name){
		//价格
		$('.tradViewContent .curList_new .List_mark_new tbody tr').eq(index - 1).children().eq(1).find('a').html(toNonExponential(deal-0));
		//涨幅
		$('.tradViewContent .curList_new .List_mark_new tbody tr').eq(index - 1).children().eq(2).find('a').html(rat == null ? '--' : rat+'%');
	return;
	return(
		'<tr currency_trade="USDT_BTC" class="currencyList_USDT_BTC ' + (index == 0 ? 'active' : "") + '" trade="BTC">' +
		'<td width="60px" class="bi_icon_new" stlye="text-align:center;">' +
		'<a href="/trade/' + data.money+'/'+data.stock+'"style="font-weight:500;font-size:100%;" >'+ '<span style="font-weight:600;">' +(data.stock)+ '</span>' +'/'+data.money +
		'</a>' +
		'</td>' +
		'<td width="50px" class="rmb_pire_new">' +
		'<a href="/trade/'+ data.money+'/'+data.stock+  '"style="color:#666;font-weight: 500;">' + deal + '</a>' +
		'</td>' +
		'<td width="30px" class="change_rate">' +
		'<a href="/trade/' + data.money+'/'+data.stock+   '" style="color:'+(rat<0?"#e62512" : "rgb(65, 155, 1);")+'">' + (rat == null ? '--' : rat+'%') + '</a>' +
		'</td>' +
		'<td width="18px" class="fav">' +
		'<b pair="USDT_BTC" class="fav_handle"></b>' +
		'</td>' +
		'</tr>'
	)
	}                           				
}

/*排序监听*/
$('.currency_list_new .tradeNav').on('click','.ascBtn',function(){
    ascBtn($(this));
})
/*搜索监听*/
$('#coinOrder').on('click','.search',function(){
//    	$(this).hide();
//		$('.favorite').find('.favorite_view').hide();
	$('.favorite').find('#search').show();
	$('#search').find('#search_input').focus();
//		$('.currency_list_new .tradeNav.curView_New').find('.tradViewSearch').show().siblings().hide();
	curList = [];
    $('.tradViewSearch').find('.List_mark_new tbody tr').each(function(index,item){
        curList.push($(item).attr('trade'));
    })
})
$('#coinOrder').find('#search .search_left').on('click','em',function(){
	$('#search').find('#search_input').val('');
	$('#search').find('#search_input').focus();
	$('.curView_New').find('.tradViewSearch').find('.curView_New tbody tr').show();
	$('.curView_New').find('.tradViewSearch').find('.curView_New tbody tr').show();
})
/*搜索筛选*/
$('.favorite').find('#search').on('keyup','input',function(){
    OrderSearch($(this).val(),$(this));
})


var m = 0;
$('#favorite_view').on('click', 'li', function() {
	var index = $(this).index();
	m=index-1;
	//console.log(index)
	$('#favorite_view li').removeClass('active').eq(index).addClass('active');
	//$('.tradView').css('display', 'none').eq(index).css('display', 'block');
	if(index==0){
		$('.tradViewFav').css('display', 'block');$('.tradViewContent').css('display', 'none');
	}else{
		$('.tradViewContent').css('display', 'block');$('.tradViewFav').css('display', 'none');
    	//console.log(allList[index - 1].main)
		$('.tradViewContent .List_mark_new tbody').empty();
		mapWebSco(allList[index - 1].list, allList[index - 1].main) 
   }
})

clearInterval();
setInterval(function(){
	//5秒更新一次市场行情，判断当前选中币种
	for (var i = 0; i < $('#favorite_view li').length; i++) {
		if ($('#favorite_view li').eq(i).hasClass('active')) {
			m=i-1;
		}
	}
	mapWebSco(allList[m].list,allList[m].main)
},10000);

$('.tradView:not(.tradViewFav) .List_mark_new tbody').on('mouseenter','tr',function(){
	var index = $(this).index();
	http.sendData({
			"id": 3,
			"method": "today.query",
			"params": [allList[m].list[index].name]
	})
	window.revieceData3 = function(re){
		if(re.id==3){
			//console.log(re);
			var res = re.result;
			var rat = res &&  (res.last-res.open)/res.open
			rat = (rat*100).toFixed(2)
			var last = res && res.last || '--';
			var deal = res && res.deal || '--';
			var high = res && res.high || '--';
			var low = res && res.low || '--';
			//var mon = utc;
			var mon = allList[m].list[0].exchange_rate_cny;
			
			var nam = allList[m].list[index].stock +'/'+allList[m].list[index].money
            var m3 = allList[m].list[index].money					}
	}
	//当前市场
	window.revieceData4 = function(re){
		if(re.id == 4){
			//console.log(1111);
			var res = re.result;
			var rat = res &&  (res.last-res.open)/res.open
			rat = (rat*100).toFixed(2);
			var last = res && res.last || '--';
			var high = res && res.high || '--';
			var low = res && res.low || '--';
			var n = stock+'/'+money
            var m2 = money;
            var s2 = stock;
			var mon = utc;
		}
	}
    }).on('mouseleave',function(){
	$('.coin_coin img').attr('src',exchangeD&&exchangeD.stock_icon);
	http.sendData({
			"id": 4,
			"method": "today.query",
			"params": [changeName]
	})
})
			
//买入
var bidsData = {};
$('.numberin_new').on('keyup',function(){
	//console.log($(this).attr('name'));
	var NAME = $(this).attr('name')
	buyRat($(this),NAME)
})
function buyRat(ev,type){
	var rat = ev.val();
	$('.mainUnit').css('display','block')
	var e = ev.val();
	var h = $('.sell_num .mainUnit i').html();
	if(type=='buynum'){
		bidsData.amount = e;
		var v = $('#coinpricein_new').val();
		//bidsData.pride = v;
		$('#coinAmount_new').val((v*ev.val()-0).toFixed(4))
			if(e-0<=h-0){
				$('#trustbtnin').attr('disabled',false);
			}else{
				$('#trustbtnin').attr('disabled',true);
			}
	}
	if(type=='buyprice'){
		var y = (buymoney/e).toFixed(4);
		$('.sell_num .mainUnit i').html(y);
		$('.buyform .unit2 i').html('NT$≈'+((utc*e-0).toFixed(4)||''))
	}				
}
$("#numberin_new").on('blur',function(){
	//console.log(222)
	$('.mainUnit').css('display','none')
})
	
var numB = 0
function buyCoin(l,i){
			http.post('bargain/order-limit',bidsData,function(res){
				http.info(res.message)
				l.close(i);
				$('#numberin_new').val('0');
				$('#coinAmount_new').val('0')
				$('#trustbtnin').attr('disabled',true)
				$('.buy .pointer-label').html('0%');
				deepExchange();
				data30();
				lookForSX();
				send61(1);
			},function(err){
				http.info(err.message);
			})
		
}
$('#trustbtnin').click(function(){
	buy_new()
})
function buy_new(){
		bidsData.market = changeName;
		bidsData.side = 2;
		bidsData.pride = $('#coinpricein_new').val();
		bidsData.amount = $('#numberin_new').val();
		lookForSX();
		//console.log(bidsData.amount)
		if(bidsData.amount){
			if(bidsData.pride){
			http.confirmTip('确定买入？',function(i,e,l){		
	 			buyCoin(l,i)
	 			})
				 }else{
					http.info('金额不能为空')
				}
	
			}else{
				http.info('数量为空')
			}
	
}
function allMoney(ev){
}
//卖出
var saleData={}
$('.numberout_new').on('keyup',function(){
	var NAME = $(this).attr('name');
	saleRat($(this),NAME);
})

function saleRat(ev,type){
	var rat = ev.val();
	$('.mainUnit').css('display','block')
	if(type=='priceout'){
		$('.buyform .unit2 i').html('NT$≈'+((utc*ev.val()-0).toFixed(4)||''))
		bidsData.pride = ev.val();
	}
	if(type =='numberout'){
		saleData.amount = ev.val();
		var h = $('.buy_num .mainUnit i').html()
		var s = $('#coinpriceout_new').val();
		if(ev.val()-0<h-0){
			$('#trustbtnout').attr('disabled',false);
		}else{
			$('#trustbtnout').attr('disabled',true);
		}
		saleData.pride = s;
		$('#coinpriceAmount_new').val((s*ev.val()-0).toFixed(4));
	}
}
$('#numberout_new').blur(function(){
	$('.mainUnit').css('display','none')
})


function sellCoin(l,i){
	http.post('bargain/order-limit',saleData,function(res){
		http.info(res.message);
		l.close(i);
		$('.sell #numberout_new').val('0');
		$('.sell #coinpriceAmount_new').val('0')
		$('.sell .selected-bar').css('width','0px');
			deepExchange();
			data30();
			lookForSX()
			send61(1)
			$('#trustbtnout').attr('disabled',true)

	},function(err){
		http.info(err.message)
	})
}
$('#trustbtnout').click(function(){
	sell_new()
})
function sell_new(){
	saleData.market = changeName;
	saleData.side = 1;
	saleData.pride = $('#coinpriceout_new').val();
	saleData.amount = $('#numberout_new').val();
	lookForSX();
	if(saleData.amount){
			if(saleData.pride){
				http.confirmTip('确定卖出？',function(i,e,l){
		 			sellCoin(l,i)
            })
			}else{
				http.info('金额不能为空')
			}

		}else{
			http.info('数量为空')
		}
}
lookForSX()
function lookForSX(){//查询余额
	http.post('bargain/balance', {//余额
						asset_type: stock + '|' +money
				}, function(res) {
					//console.log('----')
					//console.log(res);
					$('.balance_view tbody').empty();
					$.each(res.data && res.data.list,function(i,ev){
							$('.balance_view tbody').append(
								'<tr height="24" class="">' +
								'<td class="currency_mark" width="72">' + ev.name + '</td>' +
								'<td class="total" width="134">' + ev.available + '</td>' +
								'<td class="available balance" width="154">' + ev.available + '</td>' +
								'</tr>')	
					})
				
			})
}
function data30(){
		var token = localStorage.getItem('access_token')
		http.sendData({
		"id": 30,
		"method": "server.auth",
		"params": [token + '|web', 'web']
		})
}
function deepExchange(){
	http.sendData({
			"id": 17,
			"method": "depth.subscribe",
			"params": [changeName, 10, '0']
		})
		
}
function Pong(){
	http.sendData({
			"id": 20,
			"method": "server.ping",
			"params": []
		})
}
var typ = ''//区分首次进入页面获取数据量
function send61(type){
	typ = type;
	http.sendData({
		"id": 61,
		"method": "deals.subscribe",
		"params": [changeName]
	})
}
var sbCoinIndex = 0;
//$(function() {
var token = localStorage.getItem('access_token');
if(token)$('.noLogin').css('display','none');
function makeUp(data,type) { //挂单；
	//console.log(data)
	if(data){
		if(!( data.length >= 10)) { //遍历
			for(var i = 0, len = 10 - data.length; i < len; i++) {
				switch(type){
					case 1:
						data.unshift(['--', '--'])
					break;
					case 2:
					   data.push(['--','--'])
					break;
				}
				
			}
		}else{
			data = data.slice(0,10);
		}
	}
	return data
}
		
var timer;
clearInterval(timer);
Pong();
deepExchange();
data30();
send61(2);
	
timer= setInterval(function(){
	//deepExchange();
	data30();
	//send61(1);
	send1();
},10000)

/*Transactions Subscribe接口 最新成交价格{"id":56,"method":"price.subscribe","params":["BTCUSDT"]}*/
window.revieceData61 = function(es) {
	var params = es.params;

	if(params[1].length >= 10)$('#coinorderlistNew tbody').empty();
	//if(typ == 2){if(params[1].length < 10){$('#coinorderlistNew tbody').empty();}}
	if(params) {
		params = params && params[1];
		var data = params.slice(0,20);
		
		data.map(function(e, index) {
			var timer = new Date(e.time * 1000);
			var month = timer.getMonth()+1;
			var nowDate = timer.getDate();
			var hours = timer.getHours();
			var min = timer.getMinutes();
            var sec =timer.getSeconds();
			min = min>=10?min:'0'+min
            sec = sec>=10?sec:'0'+sec
            hours = hours>=10?hours:'0'+hours
			timer = hours + ':' + min + ':' + sec;
			if(params.length <=10)$('#coinorderlistNew tbody').prepend('<tr height="20" ><td class="list_con1 trade_time" width="40">' + timer + '</td>   <td class="list_con1 ' + e.type + ' price_new priceNum" width="120" price="' + e.price + '">' +
										toNonExponential(e.price-0) + '</td><td class="list_con1 num ' + e.type + ' " width="120">' +((e.amount-0).toFixed(4)) + '</td>  <td  width="15" class="tdClass"> <div id="newTitle"></div></td></tr>')

			else  $('#coinorderlistNew tbody').append('<tr height="20" ><td class="list_con1 trade_time" width="40">' + timer + '</td>   <td class="list_con1 ' + e.type + ' price_new priceNum" width="120" price="' + e.price + '">' +
				 toNonExponential(e.price-0) + '</td><td class="list_con1 num ' + e.type + ' " width="120">' +((e.amount-0).toFixed(4)) + '</td>  <td  width="15" class="tdClass"> <div id="newTitle"></div></td></tr>')

		})
	}
}
$(' #coinorderlistNew tbody').on('mouseenter','tr',function(){
	var d = $(this).children('.tdClass').children('#newTitle');
	var v= $(this).children('.priceNum').html()
	d.html('≈NT$'+((utc*v).toFixed(4))).css('display','block')
}).on('mouseleave','tr',function(){
	$(this).children('.tdClass').children('#newTitle').css('display','none')
})
		
		
		
		
		
var startA = null;
var startB = null;
var nw = 0;
function singalFunc(start,singal){
	for(var i = 0,len = start.length;i<len;i++){
		singal.push(start[i][0])
	}
	singal.sort(function(a,b){
		return (a-0)<(b-0);
	})
	return singal;
}
var singalA = [];//初始查询
var singalB = [];//初始查询;
function addFirst(singalA){
	if(singalA.length<10){
		for(var j = 0,len = 10-singalA.length;j<len;j++){
			singalA.unshift('--');
		}
	}
}
		window.revieceData17 = function(res) {
//			console.log('17')
//			console.log(res)
			/*console.log('17')*/
			var params = res.params && res.params[1];
			if(params) {
				var asks = params.asks;
				var bids = params.bids;
				nw ++;
				if(nw == 1){//保存原始数据；
					startA = asks;
					singalA = singalFunc(startA,singalA);
					if(singalA.length<10){
						for(var j = 0,len = 10-singalA.length;j<len;j++){
							singalA.unshift('--');
						}
					}
					startB = bids;
					singalB = singalFunc(startB,singalB)
					
				}
				if(res.params[0]==true){
					asks.reverse();
					asks = makeUp(asks,1)
					bids = makeUp(bids,2)
					nowBids = bids;
					nowAsk = asks;
//					console.log(nowBids);
//					console.log(nowAsk);
				}else if(res.params[0] == false){
//					console.log(nowAsk);
//					console.log(nowBids);
					if(params.bids&&params.bids.length>0){
						$.each(bids, function(x,y) {
							var IndexN = singalB.indexOf(y[0]);
							if(IndexN>0){
								if(y[1]=='0'){
									//startB.splice(IndexN,1,['--','--']);
								}else{
									startB.splice(IndexN,1,y)
								}
							}else{
								startB.splice(0,1,y)
							}
						});
						nowBids = startB;
						nowBids = nowBids.sort(function(a,b){
							//console.log(a[0]-0)
							return (a[0]-0)<(b[0]-0)
						})
						//console.log(startB);
//						console.log(nowBids);
					}
					if(params.asks&&params.asks.length>0){//卖
						//console.log(asks);
						$.each(asks, function(x,y) {
							var IndexN = singalA.indexOf(y[0]);
							var INDEX = singalA.lastIndexOf('--');
							//console.log(singalA);
							
							if(IndexN>0){
								if(y[1]=='0'){
									startA.splice(IndexN,1,['--','--'])
								}else{
									startA.splice(IndexN,1,y)
								}
								
							}else{
								if(INDEX>0){
									startA.splice(INDEX,1,y)
								}else{
									startA.splice(0,1,y)
								}
								
							}
						});
						nowAsk = startA;
						nowAsk = nowAsk.sort(function(a,b){
							//console.log(a[0]-0)
							return (a[0]-0)<(b[0]-0)
						})
//						console.log(nowAsk);
					}
					
					
				}
				var total = 0;
				$('#sellCoin').empty();
				nowAsk&&nowAsk.map(function(r, index) { //卖出
					var b = 0;
					var rat = 0;
					var singalArr = [];
					var numberArr = [];
					var indexArr = [];
					
					var io = 0;
					//if(total >= 0) {
						nowAsk.filter(function(e, i) {//过滤不存在的数据
							io++;
							if(e[1] >= 0) {
								singalArr.push(e[1])
								//indexArr.push(io)
							}
							indexArr.push(io)
						})
						indexArr.reverse();
						$.each(nowAsk, function(index,n) {//计算总量
							if(n[1]>=0){
								b += Number(n[1])
								//console.log((b-0).toFixed(4))
								numberArr.push((b-0).toFixed(4));
								
							}
						});
						numberArr.reverse();
						$.each(nowAsk, function(index,n) {//计算总量
							if(!(n[1]>=0)){
								//console.log(11)
								numberArr.unshift('--');
								
							}
						});
						total += Number(singalArr[index]);
						rat = numberArr[index]/ b;
						rat = rat.toFixed(4)-0;
						total = total.toFixed(4)-0;
//					}
					var ra = '--'
					if(r[1]!='--')ra = (r[1]-0).toFixed(4);
					//console.log(indexArr);
						$('#sellCoin').append(
							'<tr height="20">' +
							'<td class="sell" width="50"><span class="title">卖' + (indexArr[index]) + '</span><span class="depth sell" style="width:' + (380.00 * rat) + 'px;"></span></td>' +
							'<td class="priceNum sell" width="117"  price="' + ((r[0]-0).toFixed(decimal_length-0)) + '"><span>' + ((r[0]-0).toFixed(decimal_length-0)) + '</span></td>' +
							'<td width="104" >' + (ra) + '</td>' +
							'<td width="105">' +  (numberArr[index])  + '</td>' +
							'</tr>'
						)
				})
				var bTotal = 0;
				var b = nowAsk&&(nowAsk[nowAsk.length-1][0] ? nowAsk[nowAsk.length-1][0] : 0)||'0'
			
				var a = nowBids&&(nowBids[0][0] ? nowBids[0][0] : 0)||'0'
				//console.log(nowBids);
				//$('#coinpriceout_new').val(a);
				var newR = [];
				$.each(nowAsk, function(index,r) {
					if(r[0] != '--'){
						newR.push(r[0])
					}
				});
				
				$('#buyCoin').empty();
				
				nowBids&&nowBids.map(function(r, index) { //买入
					bTotal += Number(r[1]) //对前一个数的叠加
					//console.log(bTotal);
					//console.log(bTotal)
					var b = 0; //叠加总数
					var rat = 0; //比率
					if(bTotal >= 0) {
						var d = nowBids.filter(function(e, i) {
							if(e[1] >= 0) {
								b += Number(e[1]);
							}
							return e[1] >= 0
						})
						
						//console.log(b)
						//inArr.reverse();
						rat = bTotal / b;
						rat = rat.toFixed(6)
						//bTotal = bTotal.toFixed(6) - 0;
						
					}
					var ra = '--'
					if(r[1]!='--')ra = (r[1]-0).toFixed(4);
						$('#buyCoin').append(
							'<tr height="20">' +
							'<td class="buy" width="50"><span class="title">买' + (index+1) + '</span><span class="depth buy" style="width: ' + (380.00 * rat) + 'px;"></span></td>' +
							'<td class="priceNum buy" width="117"  price="' + ((r[0]-0).toFixed(decimal_length-0)) + '"><span>' + ((r[0]-0).toFixed(decimal_length-0)) + '</span></td>' +
							'<td width="104" id="numberN">' + ra + '</td>' +
							'<td width="105">' + (bTotal >= 0 ? bTotal.toFixed(4) : '--') + '</td>' +
							'</tr>'
						)

				})
			}
		}

$('.head').click(function() {
	//console.log($(this))
	$('.head').removeClass('active')
	$(this).addClass('active')
	var index = $(this).index();
	sbCoinIndex = index;
	//console.log(index)
	if(index == 0) $('.formareaView').stop(true).animate({
		left: 0
	})
	if(index == 1) {
		$('.formareaView').stop(true).animate({
			left: '-380px'
		})
		var a = nowBids&&(nowBids[0][0] ? nowBids[0][0] : 0)||'0';
							
		var num2 =  nowBids&&(nowBids[0][1] ? nowBids[0][1] : 0)||'0';
		
		$('#coinpriceout_new').val(a)
		
		//if(num2-0>ev.available-0)num2 = ev.available;
		$('#numberout_new').val(num2);
		$('#coinpriceAmount_new').val(num2*a);
		//$('.buy_num .mainUnit i').html(ev.available);
	}
})
		
		//买入卖出
		$('#buyCoin').on('click', 'tr', function() { //bids
			var index = $(this).index();


			var nb = nowBids&&nowBids[index]&&nowBids[index][0];
			var nb1 = nowBids&&nowBids[index]&&nowBids[index][1];
			var mon = utc*nb;
			var pr = (buymoney/nb).toFixed(4);
			switch(sbCoinIndex){
				case 0://买入
					$('.buy .unit2 i').html('≈NT$'+((mon-0).toFixed(4)||''));
					$('#coinpricein_new').val((nb-0).toFixed(8));
					$('.sell_num .mainUnit i').html(pr);
					if(nb1-0>=pr-0)nb1=pr;
					$('#numberin_new').val((nb1-0).toFixed(8));
					$('#coinAmount_new').val(((nb1*nb)-0).toFixed(2)||'');
					$('#trustbtnin').attr('disabled',false)
				break;
				case 1://卖出
					$('#coinpriceout_new').val((nb-0).toFixed(8));
					$('.sell .unit2 i').html('≈NT$'+((mon-0).toFixed(4)||''));
					var sellMax = $('.buy .mainUnit i').html();
					if(nb1-0>sellMax-0)nb1=sellMax;
					$('#numberout_new').val((nb1-0).toFixed(8));
					$('#coinpriceAmount_new').val(((nb1*nb)-0).toFixed(2));
					$('#trustbtnout').attr('disabled',false)
					
				break;
			}
			/*sessionStorage.setItem('askStock',nowAsk[index][1])
			sessionStorage.setItem('bidsStock',nowBids&&nowBids[index]&&nowBids[index][1])*/
		}).on('mouseenter','tr',function(){
			var pv = $(this).children('.priceNum').children().html();
			if(pv == '--')pv = '';
			$(this).append('<div id="title">≈NT$'+((utc*pv).toFixed(2))+'</div>')
		}).on('mouseleave','tr',function(){
			$(this).children('#title').remove()
		})
		$('#sellCoin').on('click', 'tr', function() { //nowAsk
			var index = $(this).index();
			var na =nowAsk && nowAsk[index] && nowAsk[index][0];
			var na1 = nowAsk && nowAsk[index] && nowAsk[index][1];
			var mon =utc*na;
			var pr = (buymoney/na).toFixed(4);
				switch(sbCoinIndex){
					case 0://买入
						var monprice = (mon-0).toFixed(2)!==NaN?(mon-0).toFixed(2):""
						$('.buy .unit2 i').html('≈NT$'+monprice);
						$('#coinpricein_new').val((na-0).toFixed(8));
						$('.sell_num .mainUnit i').html(pr);
						if(na1-0>=pr-0)na1 = pr;
						$('#numberin_new').val((na1-0).toFixed(8));
						//var 
						$('#coinAmount_new').val(((na1*na)-0).toFixed(2));
						$('#trustbtnin').attr('disabled',false)
					break;
					case 1://卖出
						$('#coinpriceout_new').val(((nowAsk[index][0])-0).toFixed(8));
						//console.log((mon-0).toFixed(2));
						var monprice = (mon-0).toFixed(2)!==NaN?(mon-0).toFixed(2):""
						$('.sell .unit2 i').html('≈NT$'+monprice);
						var buyMax = $('.sell .mainUnit i').html();
						if(na1-0>=buyMax-0)nb1=buyMax;
						$('#numberout_new').val((na1-0).toFixed(8));
						$('#coinpriceAmount_new').val(((na1*na)-0).toFixed(2));
						$('#trustbtnout').attr('disabled',false)
					break;
				}
		}).on('mouseenter','tr',function(){
			var pv = $(this).children('.priceNum').children().html();
			if(pv == '--')pv = '';
			$(this).append('<div id="title">≈NT$'+((utc*pv).toFixed(2))+'</div>')
		}).on('mouseleave','tr',function(){
			$(this).children('#title').remove()
		})
		//委托历史
		//data30()
		window.revieceData30 = function(res) {
			
			if(res.result && res.result.status == 'success') {
				http.sendData({
					"id": 31,
					"method": "order.query",
					"params": [changeName, 0, 50]
				})
				http.sendData({
					"id": 32,
					"method": "order.history",
					"params": [changeName, 0, 0, 0,10,0]
				})
			}
		}
		window.revieceData31 = function(res) {//委托
			//console.log('历史')
			//console.log(res)
             if(res.result) {
				var records = res.result.records;
				$('#mycointrustlist_new').empty();
				records.map(function(res, index) {
					// http.post('bargain/cancel-order', {
					// 	market:res.market,
					// 	order_id: res.id
					// }, function(res) {
					// 	http.info(res.message)
					// 	// ev.parent().parent().remove()
					// 	// n.close(index);
					// })

					var timer = new Date((res.ctime) * 1000)
					var month = timer.getMonth()+1
					var nowDate = timer.getDate();
					var hours = timer.getHours();
					var min = timer.getMinutes();
					min = min>=10?min:'0'+min
					$('#mycointrustlist_new').append('<tr height="30">' +
						'<td class="header" width="7%" style="font-size:15px;text-align:center;color:'+(res.side==1?"#e55600;":(res.side==2?"#090;":""))+'">'+(res.side==1?"<span class='spanStyle' style='font-size:20px ;'></span>卖":(res.side==2?"<span  class='spanStyle buyStyle' style='font-size:20px ;'></span>买":""))+'</td>' +
						'<td class="header" width="17%" data-i18n="b_10">' + res.market + '</td>' +
						'<td class="header" width="15%" data-i18n="b_11">' + month + "-" + nowDate + '&nbsp;' + hours + ':' + min + '</td>' +
						'<td class="header" width="20%" data-i18n="b_l2">' + res.price+'/'+(res.deal_money-0).toFixed(4)  + '</td>' +
						'<td class="header" width="20%" data-i18n="b_13">' + res.amount+'/'+((res.amount-res.left).toFixed(4))+ '</td>' +
						'<td class="header" width="10%" data-i18n="b_14">' +
						'<a href="javscript:;" data-id="'+res.id+'"  id="cancelOrder" style="color: #666666;text-decoration: none;font-size:12px;padding: 6px;border-radius: 15px;">撤销</a>' +
						'</td>' +
						'</tr>');
					
				})
			}
          }
			window.revieceData32 = function(r) {
             if(r.result) {
				var data = r.result && r.result.records;
				//data = data && data.slice(0,10);
				//console.log(r)
				$('#myHistoryList_new').empty();
				$.each(data,function(index, r) {					
					var timer = new Date((r.ctime) * 1000)
					//console.log(timer)
					var month = timer.getMonth()+1
					var nowDate = timer.getDate();
					var hours = timer.getHours();
					var min = timer.getMinutes();
					min = min>=10?min:'0'+min
					$('#myHistoryList_new').append('<tr height="30">' +

						'<td class="header" width="7%" style="font-size:15px;text-align:left;color:'+(r.side==1?"#e62512;":(r.side==2?"#090;":""))+'">'+(r.side==1?"<span class='spanStyle' style='font-size:20px ;'></span>卖":(r.side==2?"<span  class='spanStyle buyStyle' style='font-size:20px ;'></span>买":""))+'</td>' +						
						'<td class="header" width="17%" style="text-align:left;" data-i18n="b_10">' + r.market + '</td>' +
						'<td class="header" width="15%" style="text-align:left;" data-i18n="b_11">' + month + "-" + nowDate + '&nbsp;' + hours + ':' + min + '</td>' +
						'<td class="header" width="20%" style="text-align:left;" data-i18n="b_l2">' + r.price+'/'+(r.deal_money-0).toFixed(2) + '</td>' +
						'<td class="header" width="20%" style="text-align:left;" data-i18n="b_13">' + r.amount+'/'+(r.deal_stock-0).toFixed(2)+ '</td>' +
						'<td class="header" width="10%" style=";text-align:left;" data-i18n="b_14">' +
						'<a href="javscript:;" id="cancelOrder" style="text-align:left;color: #666666;text-decoration: none;font-size:12px;">已成功</a>' +
						'</td>' +
						'</tr>');
					//tool.toBottom('.myHistoryList','.myHistoryList tr:last');
				})

			}
		}
//	})
	$('#mycointrustlist_new').on('click','#cancelOrder',function(){
		//console.log($(this))
		var dataId = $(this).attr('data-id');
		//console.log(dataId);
		var index = $(this).index();
		cancel($(this),dataId,index)
	})
	function cancel(ev,r,i){
		var $this = this
		http.confirmTip('确认撤销？',function(index,layero,n){
				http.post('bargain/cancel-order', {
					market:changeName,
					order_id: r
				}, function(res) {
					http.info(res.message)
					ev.parent().parent().remove()
					n.close(index);
				})
		})
	}

}

function cancel_all(){
	http.confirmTip('确认全部撤销？',function(index,layero,n){
			http.post('robot/cancel_all', {
				market:stock_money,
			}, function(res) {
				http.info(res.message)
			});
			n.close(index);
	})
}