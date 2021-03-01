
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo Yii::$app->config->info('WEB_APP_NAME'); ?></title>

    <style type="text/css">
        *
        {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            -ms-box-sizing: border-box;
            -o-box-sizing: border-box;
            box-sizing: border-box;
        }
        html
        {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        body
        {
            margin-top: 0px;
            background-attachment: fixed;
            /* [disabled]color:White; */
            font-size: 16px;
            width: 100%;
            height: 100%;
            font-family: 'Open Sans' , sans-serif;
            text-align: center; padding-top: 3px; overflow: hidden; position: relative;    background: url(/resource/frontend/img/bg.png) no-repeat;    background-size: 100% 100%;
        }
        .shade{display:none;z-index:110;position:fixed;width:100%;width:100vw;height:100%;height:100vh;background:rgba(0,0,0,.8);top:0;left:0;opacity:1}
        .shade img{
            width: calc(100vh*9/16 - 2rem);
            position: absolute;
            right: 0.5rem;
            display: block;
            z-index: 2;
        }
        .tishi{width: 80%;margin: auto;display: block;}
        .mask{margin: 0;padding: 0;height: 100%;width: 100%;overflow: hidden;}
        .mask{position: absolute;background-color: #000;opacity: 0.8;top: 0;left: 0;display: none;}
        .jump {
            color: white !important;
            font-size: 2.2em;
            text-decoration: none;
            display: inline-block;
        }
        .open-browser {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            display: none;
            z-index: 100;
            color: #fff;
            background: rgba(0,0,0,.7) url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTAycHgiIGhlaWdodD0iMTMzcHgiIHZpZXdCb3g9IjAgMCAxMDIgMTMzIiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPCEtLSBHZW5lcmF0b3I6IFNrZXRjaCA1Mi41ICg2NzQ2OSkgLSBodHRwOi8vd3d3LmJvaGVtaWFuY29kaW5nLmNvbS9za2V0Y2ggLS0+CiAgICA8dGl0bGU+6Lev5b6EIDI8L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8ZyBpZD0iSU9TLea1t+Wkli3lrprnqL8tQ29weSIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTU3Ni4wMDAwMDAsIC0xNzYuMDAwMDAwKSIgZmlsbD0iI0ZGRkZGRiIgZmlsbC1ydWxlPSJub256ZXJvIj4KICAgICAgICAgICAgPHBhdGggaWQ9Iui3r+W+hC0yIiBkPSJNNjY2LjQ0Mzk2LDE4Ny41Mjk4MjQgTDY0OC42NTg4MDgsMTk4LjA2ODc5OCBDNjQ3LjIzMzQxOCwxOTguOTEzNDQ0IDY0NS4zOTMxODksMTk4LjQ0MjY1NyA2NDQuNTQ4NTQzLDE5Ny4wMTcyNjYgQzY0My43MDM4OTcsMTk1LjU5MTg3NSA2NDQuMTc0Njg0LDE5My43NTE2NDcgNjQ1LjYwMDA3NSwxOTIuOTA3MDAxIEw2NzMuMjM3ODIzLDE3Ni41Mjk2NTggQzY3NS4yNTAzOTksMTc1LjMzNzA2MyA2NzcuNzkyOTIxLDE3Ni44MDQ1NDQgNjc3Ljc2NzAwNiwxNzkuMTQzNzkgTDY3Ny40MTExMjcsMjExLjI2NzU0OCBDNjc3LjM5Mjc3MywyMTIuOTI0MzAxIDY3Ni4wMzQ4MywyMTQuMjUyNDg1IDY3NC4zNzgwNzgsMjE0LjIzNDEzMSBDNjcyLjcyMTMyNSwyMTQuMjE1Nzc3IDY3MS4zOTMxNDEsMjEyLjg1NzgzNSA2NzEuNDExNDk1LDIxMS4yMDEwODIgTDY3MS42NDA1MDksMTkwLjUyODkzOSBMNjYzLjM0ODU3MywyMDQuODk1MDEzIEM2MzIuNTE2NTkyLDI1Ni42OTk3MyA2MDUuMDMwODg2LDI5MS4yMDAwNjUgNTgwLjczNjU4MiwzMDguNDQ2Mjc5IEM1NzkuMzg1NTQsMzA5LjQwNTM2NyA1NzcuNTEyODA5LDMwOS4wODc2MjUgNTc2LjU1MzcyMSwzMDcuNzM2NTgyIEM1NzUuNTk0NjMzLDMwNi4zODU1NCA1NzUuOTEyMzc1LDMwNC41MTI4MDkgNTc3LjI2MzQxOCwzMDMuNTUzNzIxIEM2MDAuNzI0MDMyLDI4Ni44OTkzMzIgNjI3Ljc1MjA2MiwyNTIuOTczNDc2IDY1OC4xNzIzMywyMDEuODYxMDU2IEw2NjYuNDQzOTYsMTg3LjUyOTgyNCBaIj48L3BhdGg+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4=) no-repeat;
            background-position: right 50px top 60px;
            background-size: 10em auto;
        }
        .open-browser p {
            padding-top: 5.2em;
            font-size: 3em;
        }
    </style>
</head>
<body  >
<div class="content" style=" padding-top:3px;">
    <div class="row">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <div class="box">





                <div style="position:absolute;width:100%;bottom:40%;z-index: 25;">
                    <a href="<?php echo Yii::$app->config->info('APP_DOWNLOAD_IOSURL'); ?>">
                        <img src="/resource/frontend/img/iphonedown.png" class="img-responsive center-block" style="border:0">
                    </a>
                    <p style="height: 50px;">
                    </p>
                    <p>
                        <a id="android-link" href="<?php echo Yii::$app->config->info('APP_DOWNLOAD_ANDURL'); ?>">
                            <img src="/resource/frontend/img/androiddown.png" class="img-responsive center-block" style="border:0">
                        </a>
                    </p>
                    <p style="height: 50px;">
                        <a href="/app" class="jump">进入网页 ››</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4"></div>
        <div class="open-browser"><p>请点击右上角···<br>选择“在浏览器中打开”</p></div>
    </div>
</div>
<script type="text/javascript">
    var browser = {
        versions: function () {
            var u = navigator.userAgent, app = navigator.appVersion;
            return { //移动终端浏览器版本信息
                ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
                android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或uc浏览器
                iPhone: u.indexOf('iPhone') > -1, //是否为iPhone或者QQHD浏览器
                iPad: u.indexOf('iPad') > -1, //是否iPad
                pc: u.indexOf('Windows') > -1, //是否iPad
            };
        }(),
        isWeChat: function () {
            var ua = navigator.userAgent.toLowerCase();
            return ua.match(/MicroMessenger/i) == "micromessenger"
        }()
    }
    if (browser.isWeChat) {
        document.querySelector('#android-link').addEventListener('click', function(e){
            e.preventDefault()
            document.querySelector('.open-browser').style.display = 'block'
        })
    }
    /*
    if (browser.versions.iPhone || browser.versions.iPad || browser.versions.ios) {
    window.location.href = "itms-services://?action=download-manifest&url=https://down.bibei-coin.com/1.plist";
    }
    if (browser.versions.android) {
    window.location.href = "http://down.bibei-coin.com/bibei-release.apk";
    }
    if (browser.versions.pc) {
    window.location.href = "http://ex.bibei-coin.com/download/";
    }*/

</script>

</body>

</html>