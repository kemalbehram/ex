<?php
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = '服务器信息';
$this->params['breadcrumbs'][] = ['label' => '系统', 'url' => ['/sys/system/index']];
$this->params['breadcrumbs'][] = ['label' =>  $this->title];
?>

<?= Html::jsFile('/resource/backend/js/plugins/echarts/echarts-all.js')?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="ibox float-e-margins">
        <div class="ibox-title">
            <h5><i class="fa fa-cog"></i>  服务器参数</h5>
        </div>
        <div class="ibox-content">
            <table class="table">
                <tr>
                    <td>服务器域名地址</td>
                    <td><?= $info['server']['domainIP'] ?></td>
                    <td>服务器标识</td>
                    <td><?= $info['server']['flag'] ?></td>
                </tr>
                <tr>
                    <td>操作系统</td>
                    <td><?= $info['server']['os'] ?></td>
                    <td>服务器解析引擎</td>
                    <td><?= $info['server']['webEngine'] ?></td>
                </tr>
                <tr>
                    <td>服务器语言</td>
                    <td><?= $info['server']['language'] ?></td>
                    <td>服务器端口</td>
                    <td><?= $info['server']['webPort'] ?></td>
                </tr>
                <tr>
                    <td>服务器主机名</td>
                    <td><?= $info['server']['name'] ?></td>
                    <td>站点绝对路径</td>
                    <td><?= $info['server']['webPath'] ?></td>
                </tr>
                <tr>
                    <td>服务器当前时间</td>
                    <td><span id="divTime"></span></td>
                    <td>服务器已运行时间</td>
                    <td><?= $info['sysInfo']['uptime'] ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="ibox float-e-margins">
        <div class="ibox-title">
            <h5><i class="fa fa-cog"></i>  服务器硬件数据</h5>
        </div>
        <div class="ibox-content">
            <div id="schedule" style="width: 100%;height:150px;"></div>
            <div class="col-sm-12 text-center" id="memData">
                <div class="col-sm-3 "><?= $info['hd']['u']?>/<?= $info['hd']['t']?> (G)</div>
                <div class="col-sm-3"><?= $info['sysInfo']['memRealUsed']?>/<?= $info['sysInfo']['memTotal']?> (M)</div>
                <div class="col-sm-3"><?= $info['sysInfo']['memUsed']?>/<?= $info['sysInfo']['memTotal']?> (M)</div>
                <div class="col-sm-3"><?= $info['sysInfo']['memCachedReal']?>/<?= $info['sysInfo']['memCached']?> (M)<br>Buffers缓冲为 <?= $info['sysInfo']['memBuffers']?> M</div>
            </div>
            <div id="sys-hardware">
                <table class="table">
                    <tr>
                        <td>CPU</td>
                        <td><?= $info['sysInfo']['cpu']['num'] ?></td>
                        <td>CPU型号</td>
                        <td><?= $info['sysInfo']['cpu']['model'] ?></td>
                    </tr>
                    <tr>
                        <td>CPU使用情况</td>
                        <td colspan="3">
                            <?= $info['cpuUse'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td>系统平均负载(1分钟、5分钟、以及15分钟的负载均值)</td>
                        <td colspan="3"><?= $info['sysInfo']['loadAvg'] ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="ibox float-e-margins">
        <div class="ibox-title">
            <h5><i class="fa fa-cog"></i>  服务器实时网络数据</h5>
        </div>
        <div class="ibox-content">
            <table class="table">
                <tr>
                    <td>总发送</td>
                    <td id="netWork_allOutSpeed"><?= $info['netWork']['allOutSpeed'] ?></td>
                    <td>总接收</td>
                    <td id="netWork_allInputSpeed"><?= $info['netWork']['allInputSpeed'] ?></td>
                </tr>
                <tr>
                    <td>发送速度</td>
                    <td id="netWork_currentOutSpeed"><?= $info['netWork']['currentOutSpeed'] ?> KB/s</td>
                    <td>接收速度</td>
                    <td id="netWork_currentInputSpeed"><?= $info['netWork']['currentInputSpeed'] ?> KB/s</td>
                </tr>
                <tr>
                    <td colspan="4">
                        <div id="main" style="width: 100%;height:400px;"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script type="text/html" id="model">
    <table class="table">
        <tr>
            <td>CPU</td>
            <td>{{sysInfo.cpu.num}}</td>
            <td>CPU型号</td>
            <td>{{sysInfo.cpu.model}}</td>
        </tr>
        <tr>
            <td>CPU使用情况</td>
            <td colspan="3">
                {{cpuUse}}
            </td>
        </tr>
        <tr>
            <td>系统平均负载(1分钟、5分钟、以及15分钟的负载均值)</td>
            <td colspan="3">{{sysInfo.loadAvg}}</td>
        </tr>
    </table>
</script>

<script type="text/html" id="mem">
    <div class="col-sm-3 ">{{hd.u}}/{{hd.t}} (G)</div>
    <div class="col-sm-3">{{sysInfo.memRealUsed}}/{{sysInfo.memTotal}} (M)</div>
    <div class="col-sm-3">{{sysInfo.memUsed}}/{{sysInfo.memTotal}} (M)</div>
    <div class="col-sm-3">{{sysInfo.memCachedReal}}/{{sysInfo.memCached}} (M)<br>Buffers缓冲为 {{sysInfo.memBuffers}} M</div>
</script>

<script>
    var schedule = echarts.init(document.getElementById('schedule'));

    var hdSpeed = [100, 0];
    var memTotal = [100, 0];
    var memCached = [100, 0];
    var memRealUsed = [100, 0];

    function scheduleOption() {
        var labelTop = {
            normal : {
                label : {
                    show : true,
                    position : 'center',
                    formatter : '{b}',
                    textStyle: {
                        baseline : 'bottom'
                    },
                },
                labelLine : {
                    show : false
                }
            }
        };

        var labelFromatter = {
            normal : {
                label : {
                    formatter : function (a,b,c){
                        return 100 - c + '%'
                    },
                    textStyle: {
                        baseline : 'top'
                    }
                }
            },
        }

        var labelBottom = {
            normal : {
                color: '#ccc',
                label : {
                    show : true,
                    position : 'center'
                },
                labelLine : {
                    show : false
                }
            },
            emphasis: {
                color: 'rgba(0,0,0,0)'
            }
        };
        var radius = [55, 65];
        var optionData = {
            series : [
                {
                    type : 'pie',
                    center : ['13%', '50%'],
                    radius : radius,
                    x: '0%', // for funnel
                    itemStyle : labelFromatter,
                    data : [
                        {name:'other', value: hdSpeed[0], itemStyle : labelBottom},
                        {name:'硬盘使用率', value:hdSpeed[1],itemStyle : labelTop}
                    ]
                },
                {
                    type : 'pie',
                    center : ['38%', '50%'],
                    radius : radius,
                    x:'60%', // for funnel
                    itemStyle : labelFromatter,
                    data : [
                        {name:'other', value:memRealUsed[0], itemStyle : labelBottom},
                        {name:'真实内存使用率', value:memRealUsed[1],itemStyle : labelTop}
                    ]
                },
                {
                    type : 'pie',
                    center : ['62%', '50%'],
                    radius : radius,
                    x:'20%', // for funnel
                    itemStyle : labelFromatter,
                    data : [
                        {name:'other', value:memTotal[0], itemStyle : labelBottom},
                        {name:'物理内存使用率', value:memTotal[1],itemStyle : labelTop}
                    ]
                },
                {
                    type : 'pie',
                    center : ['87%', '50%'],
                    radius : radius,
                    x:'40%', // for funnel
                    itemStyle : labelFromatter,
                    data : [
                        {name:'other', value:memCached[0], itemStyle : labelBottom},
                        {name:'Cache化内存使用率', value:memCached[1],itemStyle : labelTop}
                    ]
                }
            ]
        };

        return optionData;
    }

    schedule.setOption(scheduleOption()); // 加载图表
</script>
<script type="text/javascript">
    var myChart = echarts.init(document.getElementById('main'));
    var currentOutSpeed = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var currentInputSpeed = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    var chartTime = <?= json_encode($info['chartTime'])?>;

    function chartOption() {
        var option = {
            title : {
                subtext: '单位 KB/s'
            },
            tooltip : {
                trigger: 'axis'
            },
            legend: {
                data:['发送速度','接收速度']
            },
            toolbox: {
                show : false,
                feature : {
                    mark : {show: true},
                    dataView : {show: true, readOnly: false},
                    magicType : {show: true, type: ['line', 'bar', 'stack', 'tiled']},
                    restore : {show: true},
                    saveAsImage : {show: true}
                }
            },
            calculable : true,
            xAxis : [
                {
                    type : 'category',
                    boundaryGap : false,
                    data : chartTime
                }
            ],
            yAxis : [
                {
                    type : 'value'
                }
            ],
            series : [
                {
                    name:'发送速度',
                    type:'line',
                    smooth:true,
                    itemStyle: {normal: {areaStyle: {type: 'default'}}},
                    data:currentOutSpeed
                },
                {
                    name:'接收速度',
                    type:'line',
                    smooth:true,
                    itemStyle: {normal: {areaStyle: {type: 'default'}}},
                    data:currentInputSpeed
                }
            ]
        };

        return option;
    }

    myChart.setOption(chartOption()); // 加载图表

    $(document).ready(function(){
        setTime();
        setInterval(setTime, 1000);
        setInterval(getServerInfo, 3000);

    });

    function setTime(){
        var d = new Date(), str = '';
        str += d.getFullYear() + ' 年 '; // 获取当前年份
        str += d.getMonth() + 1 + ' 月 '; // 获取当前月份（0——11）
        str += d.getDate() + ' 日  ';
        str += d.getHours() + ' 时 ';
        str += d.getMinutes() + ' 分 ';
        str += d.getSeconds() + ' 秒 ';
        $("#divTime").text(str);
    }

    function getServerInfo(){
        $.ajax({
            type : "get",
            url  : "<?= Url::to(['server'])?>",
            dataType : "json",
            data: {},
            success: function(data){
                if(data.code == 200) {
                    var html = template('model',data.data);
                    $('#sys-hardware').html(html);
                    var html2 = template('mem',data.data);
                    $('#memData').html(html2);

                    var netWork = data.data.netWork;
                    $('#netWork_allOutSpeed').text(netWork.allOutSpeed);
                    $('#netWork_allInputSpeed').text(netWork.allInputSpeed);
                    $('#netWork_currentOutSpeed').text(netWork.currentOutSpeed + ' KB/s');
                    $('#netWork_currentInputSpeed').text(netWork.currentInputSpeed + ' KB/s');

                    currentOutSpeed.shift();
                    currentInputSpeed.shift();
                    currentOutSpeed.push(netWork.currentOutSpeed);
                    currentInputSpeed.push(netWork.currentInputSpeed);
                    chartTime = data.data.chartTime;
                    myChart.setOption(chartOption()); // 加载图表

                    //内存
                    var sysInfo = data.data.sysInfo;
                    var PCT = data.data.hd.PCT;
                    var memPercent = sysInfo.memPercent;
                    var memCachedPercent = sysInfo.memCachedPercent;
                    var memRealPercent = sysInfo.memRealPercent;

                    PCT = PCT.toFixed(0);
                    memPercent = memPercent.toFixed(0);
                    memCachedPercent = memCachedPercent.toFixed(0);
                    memRealPercent = memRealPercent.toFixed(0);
                    //console.log(sysInfo);
                    hdSpeed = [100 - PCT, PCT];
                    memTotal = [100 - memPercent, memPercent];
                    memCached = [100 - memCachedPercent, memCachedPercent];
                    memRealUsed = [100 - memRealPercent, memRealPercent];
                    schedule.setOption(scheduleOption()); // 加载图表
                }else{
                    alert(data.msg);
                }
            }
        });
    }
</script>