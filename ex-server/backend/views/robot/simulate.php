<?php
use yii\widgets\ActiveForm;

$this->title = $model->isNewRecord ? '创建' : '编辑';
$this->params['breadcrumbs'][] = ['label' => '交易配置', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$market_arr = api\models\ExchangeCoins::find()->select(['id','stock','money'])->asArray()->all();
foreach ($market_arr as $value) {
   $market_list[$value['id']] = $value['stock'].'/'.$value['money'];
}
/*
dropDownList 三个参数 1名称 2option value=>显示值 3.最上面头
*/
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>配置自动交易</h5>
                </div>

                <div id="echarts" style="height:400px;padding: 30px;"></div>


                <div class="ibox-content">
                    <?php $form = ActiveForm::begin(); ?>
                    <div class="col-sm-12">

                        <?= $form->field($model, 'robot_set_open')->textInput() ?>
                        <?= $form->field($model, 'robot_set_close')->textInput() ?>
                        <?= $form->field($model, 'robot_set_high')->textInput() ?>
                        <?= $form->field($model, 'robot_set_low')->textInput() ?>

                        <div class="hr-line-dashed"></div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12 text-center">
                            <a class="btn btn-primary" href="javascript:yulan()">预览刷新</a>
                            <a class="btn btn-primary" href="javascript:baocunshizhe()">保存设置</a>
                            <span class="btn btn-white" onclick="history.go(-1)">返回</span>
                        </div>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="/static/echarts/echarts.js"></script>

  <script type="text/javascript">

        /*基于准备好的dom，初始化echarts实例*/
        var myChart = echarts.init(document.getElementById('echarts'));

        // 数据意义：开盘(open)，收盘(close)，最低(lowest)，最高(highest)

        //切割数组，把数组中的日期和数据分离，返回数组中的日期和数据
        function splitData(rawData) {
            var categoryData = [];
            var values = [];
            
            for (var i = 0; i < rawData.length; i++) {
                //splice 返回每组数组中被删除的第一项，即返回数组中被删除的日期 
                //alert(rawData[i].splice(0, 1)[0]);
                //categoryData  日期  把返回的日期放到categoryData[]数组中
                categoryData.push(rawData[i].splice(0, 1)[0]);
                //alert(categoryData);

                //数据数组，即数组中除日期外的数据
               // alert(rawData[i]);
                values.push(rawData[i])
            }
            return {
                categoryData: categoryData, //数组中的日期 x轴对应的日期
                values: values              //数组中的数据 y轴对应的数据
            };
        }

    //计算MA平均线，N日移动平均线=N日收盘价之和/N  dayCount要计算的天数(5,10,20,30)
        function calculateMA(dayCount) {
            var result = [];
            for (var i = 0, len = data0.values.length; i < len; i++) {
                if (i < dayCount) {
                    result.push('-');
                    //alert(result);
                    continue;   //结束单次循环，即不输出本次结果
                }
                var sum = 0;
                for (var j = 0; j < dayCount; j++) {
                    //收盘价总和
                    sum += data0.values[i - j][1];
                    //alert(sum);
                }
                result.push(sum / dayCount);
               // alert(result);
            }
            return result;
        }

function refreshData(data0) {

        option = {
            title: {    //标题
                text: 'K线预览',
                left: 0
            },
            tooltip: {  //提示框
                trigger: 'axis',    //触发类型：坐标轴触发
                axisPointer: {  //坐标轴指示器配置项
                    type: 'cross'   //指示器类型，十字准星
                }
            },
            legend: {   //图例控件，点击图例控制哪些系列不现实
                data: ['日K', 'MA5', 'MA10', 'MA20', 'MA30']
            },
            grid: {     //直角坐标系
                show:true,
                left: '10%',    //grid组件离容器左侧的距离
                right: '10%',
                bottom: '15%',
                //backgroundColor:'#ccc'
            },
            xAxis: {
                type: 'category',   //坐标轴类型，类目轴
                data: data0.categoryData,
                //scale: true,  //只在数字轴中有效
                boundaryGap : false,    //刻度作为分割线，标签和数据点会在两个刻度上
                axisLine: {onZero: false},
                splitLine: {show: false},   //是否显示坐标轴轴线
                //splitNumber: 20,    //坐标轴的分割段数，预估值，在类目轴中无效
                min: 'dataMin', //特殊值，数轴上的最小值作为最小刻度
                max: 'dataMax'  //特殊值，数轴上的最大值作为最大刻度
            },
            yAxis: {
                scale: true,    //坐标刻度不强制包含零刻度
                splitArea: {
                    show: true  //显示分割区域
                }
            },
            dataZoom: [     //用于区域缩放
                {
                    filterMode:'filter',    //当前数据窗口外的数据被过滤掉来达到数据窗口缩放的效果  默认值filter
                    type: 'inside', //内置型数据区域缩放组件
                    start: 0,      //数据窗口范围的起始百分比
                    end: 100        //数据窗口范围的结束百分比
                },
                {
                    show: true,
                    type: 'slider', //滑动条型数据区域缩放组件
                    y: '90%',
                    start: 0,
                    end: 100
                }
            ],
            series: [   //图表类型
                {
                    name: '日K',
                    type: 'candlestick',    //K线图
                    data: data0.values,     //y轴对应的数据

        ////////////////////////图标标注/////////////////////////////

                    markPoint: {    //图表标注
                        label: {    //标注的文本
                            normal: {   //默认不显示标注
                                show:true,
                                //position:['20%','30%'],
                                formatter: function (param) {   //标签内容控制器
                                    return param != null ? Math.round(param.value) : '';
                                }
                            }
                        },

                        tooltip: {      //提示框
                            formatter: function (param) {
                                return param.name + '<br>' + (param.data.coord || '');
                            }
                        }
                    },

/////////////////////////////////图标标线///////////////////////////

                    markLine: {
                        symbol: ['none', 'none'],   //标线两端的标记类型
                        data: [
                            [
                                {
                                    name: 'from lowest to highest',
                                    type: 'min',    //设置该标线为最小值的线
                                    valueDim: 'lowest', //指定在哪个维度上的最小值
                                    symbol: 'circle',
                                    symbolSize: 10, //起点标记的大小
                                    label: {    //normal默认，emphasis高亮
                                        normal: {show: false},  //不显示标签
                                        emphasis: {show: false} //不显示标签
                                    }
                                },
                                {
                                    type: 'max',
                                    valueDim: 'highest',
                                    symbol: 'circle',
                                    symbolSize: 10,
                                    label: {
                                        normal: {show: false},
                                        emphasis: {show: false}
                                    }
                                }
                            ],

                            {
                                name: 'min line on close',
                                type: 'min',
                                valueDim: 'close'
                            },
                            {
                                name: 'max line on close',
                                type: 'max',
                                valueDim: 'close'
                            }
                        ]

                    }

                },

                {   //MA5 5天内的收盘价之和/5
                    name: 'MA5',
                    type: 'line',
                    data: calculateMA(5),
                    smooth: true,
                    lineStyle: {
                        normal: {opacity: 0.5}
                    }
                },
                {
                    name: 'MA10',
                    type: 'line',
                    data: calculateMA(10),
                    smooth: true,
                    lineStyle: {    //标线的样式
                        normal: {opacity: 0.5}
                    }
                },
                {
                    name: 'MA20',
                    type: 'line',
                    data: calculateMA(20),
                    smooth: true,
                    lineStyle: {
                        normal: {opacity: 0.5}
                    }
                },
                {
                    name: 'MA30',
                    type: 'line',
                    data: calculateMA(30),
                    smooth: true,
                    lineStyle: {
                        normal: {opacity: 0.5}
                    }
                },

            ]
        };


        // 使用刚指定的配置项和数据显示图表
        myChart.setOption(option);
}





var klinedata = '<?= $model->robot_set_content ?>';
if(klinedata.length>0){
    data0 = splitData(eval(klinedata));
    console.log(data0);
    refreshData(data0);    
}

var offset = 0;

function yulan(){


    var post_data={
      set_open:$('#robot-robot_set_open').val(),
      set_close:$('#robot-robot_set_close').val(),
      set_min:$('#robot-robot_set_low').val(),
      set_max:$('#robot-robot_set_high').val(),
    }
    $.ajax({
       type: 'POST',
       url: 'preview',
       dataType: 'json',
       data: post_data,
       success: function(data){
                if(data.code == 200){
                    var klinedata = eval(data.data.data);
                    data0 = splitData(klinedata);
                    console.log(data0);
                    refreshData(data0);
                    offset = data.data.offset;
                }else{
                    alert(data.message);
                }
       }
    });
}

        function baocunshizhe(){

            if (offset == 0) {
                alert('您未做任何修改,请预览刷新后重新保存');
                return;
            }


            var post_data={
              id:<?= $model->id ?>,
              set_open:$('#robot-robot_set_open').val(),
              set_close:$('#robot-robot_set_close').val(),
              set_min:$('#robot-robot_set_low').val(),
              set_max:$('#robot-robot_set_high').val(),
              offset:offset,
            }
            $.ajax({
               type: 'POST',
               url: 'submit',
               dataType: 'json',
               data: post_data,
               success: function(data){
                        if(data.code == 200){
                            var klinedata = eval(data.data.data);
                            data0 = splitData(klinedata);
                            console.log(data0);
                            refreshData(data0);
                            alert(data.message);
                        }else{
                            alert(data.message);
                        }
               }
            });
        }

    </script>