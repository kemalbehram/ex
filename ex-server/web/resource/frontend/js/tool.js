(function(window,document){
	var tool = {};
	function DetectionData(name){
		this.name = 'detection';
		this.info = function(data){ 
			layui.use(['layer','form'], function(){
					  var layer = layui.layer
					  ,form = layui.form;
					  //layer.msg(data)
					  layer.msg(data, {
						  //icon: 6,
						  time: 2000 //2秒关闭（如果不配置，默认是3秒）
						},function(){
							//console.log(11)
						});
			});
		}
		this.failer = function(data){
			layui.use(['layer','form'], function(){
					  var layer = layui.layer
					  ,form = layui.form;
					  //layer.msg(data)
					  layer.alert(data, {
						  icon: 2
						  //time: 2000 //2秒关闭（如果不配置，默认是3秒）
						},function(){
							//console.log(11)
						});
			});
		}
	}
	DetectionData.prototype = {
		constructor:'DetectionData',
		checkRule:function(obj){
			
			if(obj.name === 'phone'){//校验手机
				//console.log(obj.val.replace(/\s+/g,''));
				//if(/^1[345678]\d{9}$/.test(obj.val.replace(/\s+/g,''))){
					//console.log(obj.val);
					return true;
				//}else{
					//this.info(obj.string+'不正确，请重新输入')
				//}
			}else if(obj.name ==='codes'){//验证码6
				if(/^\d{6}$/.test(obj.val)){
					return true
				}else{
					this.info(obj.string+'不正确，请重新输入')
				}
			}else if(obj.name ==='pwd'){//密码
				/*if(/^[a-zA-Z0-9]{8,20}$/.test(obj.val)){*/
					return true
				/*}else{
					this.info(obj.string+'不正确，8-20位')
				}*/
			}else if(obj.name ==='repwd'){//密码
				/*if(/^[a-zA-Z0-9]{8,20}$/.test(obj.val)){*/
					return true
				/*}else{
					this.info(obj.string+'不正确，8-20位')
				}*/
			}
			
		},
		checkData:function(obj){//name:'校验名称',string:'提示语',val:
			if(obj.val){
				 return this.checkRule(obj)
			}else{
				this.info(obj.string+'不能为空')
			}
		}
	}
	var detectionData = new DetectionData()
	tool.detectionData = detectionData;
	tool.loginStatus = function(navHeardNo,HeaderYes,login){
		login = login || ''
		var token = localStorage.getItem('access_token')
		if(token && token !=undefined && token !=null){
			// navHeardNo.css('display','none');
			//HeaderYes.css('display','block');
			login && login.css('display','none');
		}else{
			//navHeardNo.css('display','block');
			//HeaderYes.css('display','none');
			login && login.css('display','block')
		}
	}
	tool.timerChuo = function(time,y){//y==0 年；y==1 秒；y==2,年和秒
		y = y || 0;
		var timer = new Date(time*1000);
		var year = timer.getFullYear();
		var month = timer.getMonth()+1;
		var nowDate = timer.getDate();
		var hours = timer.getHours();
		var min = timer.getMinutes();
		var second = timer.getSeconds();
		switch(y){
			case 0:
				return year+'-'+month+'-'+nowDate+'&nbsp;'+hours+':'+min
			break;
			case 1:
				return month+'-'+nowDate+'&nbsp;'+hours+':'+min+':'+second;
			break;
			case 2:
				return year+'-'+month+'-'+nowDate+'&nbsp;'+hours+':'+min+":"+second
			break;
		}
	}
	tool.toBottom = function(eleName,eleLastChild){//基于jq下的,eleLastChild 子元素最后一个元素,eleNmae父级元素
		$(eleName).animate({ scrollTop: $(eleName).scrollTop() + $(eleLastChild).offset().top - $(eleName).offset().top }, 1000);
		
	}
	function sortBase (arr,j,temp){
			temp = arr[j];
			arr[j] = arr[j+1];
			arr[j+1] = temp
	
	}
	tool.bubbleSort = function(arr,n){//n==0 s-->b 小到大  //n==1 s<---b 大 到小
		for(var i = 0,len = arr.length-1;i<len;i++){
			for(var j = 0;j<arr.length-1 - i;j++){
				var temp;
		
				switch(n){
					case 0:
					if(arr[j]<arr[j+1])sortBase(arr,j,temp);
					break;
					case 1:
						if(arr[j]>arr[j+1])sortBase(arr,j,temp);
					break;
				}
				
			}
		}
		return arr
	}
	tool.removeCF = function(arr,iArr){
		var newArr = [];
		var l = iArr.length;
		for(var j =0,len = arr.length;j<len;j++){
	
		}
		/*for(var n = 0;n<l;n++){
			newArr.push(iArr[n]);
		}*/
		return newArr;
	}
	
	window.tool = tool;
}(window,document))
