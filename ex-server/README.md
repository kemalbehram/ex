JinglanEx 数字货币交易所 V3

购买免责声明：
请勿使用本产品用于任何非法用途或违法犯罪活动，请在中国大陆以外地区运营本产品，请遵守运营当地有关数字货币监管的法律政策；产品运营过程中产生的任何法律责任或纠纷与我司无关。

产品版权声明：
打击盗版，人人有责。本产品已经进行国家软件著作权登记，任何传播本产品源代码以及二次销售本产品的行为，将会受到我司最大法律范围内的起诉。

部署说明:

1. backend/runtime目录777权限

2. web/backend/assets目录777权限

3. web/third-party/log.txt文件权限设置为777，否则ws鉴权会失败

4. web/attachment目录777权限，否则无法上传文件

5. 前端打包后放到web/static目录，替换frontend/views/index/index.php文件

6. h5打包后放到app目录

7. web/backend/jpush.log文件777权限
