<?php
namespace jianyan\basics\backend\modules\wechat\controllers;

use yii;
use yii\data\Pagination;
use yii\web\Response;
use jianyan\basics\common\models\wechat\Qrcode;

/**
 * 二维码管理
 *
 * Class QrController
 * @package jianyan\basics\backend\modules\wechat\controllers
 */
class QrController extends WController
{
    /**
     * 首页
     */
    public function actionIndex()
    {
        $request  = Yii::$app->request;
        $type     = $request->get('type',1);
        $keyword  = $request->get('keyword');

        $where = [];
        if($keyword)
        {
            if($type == 1)
            {
                $where = ['like', 'name', $keyword];// 标题
            }
        }

        $data = Qrcode::find()->where($where);
        $pages = new Pagination(['totalCount' =>$data->count(), 'pageSize' =>$this->_pageSize]);
        $models = $data->offset($pages->offset)
            ->orderBy('append desc')
            ->limit($pages->limit)
            ->all();

        return $this->render('index',[
            'models'  => $models,
            'pages'   => $pages,
            'type'    => $type,
            'keyword' => $keyword,
        ]);
    }

    /**
     * 创建
     *
     * @return string|yii\web\Response
     */
    public function actionAdd()
    {
        $model = new Qrcode();
        $model->loadDefaultValues();

        if ($model->load(Yii::$app->request->post()) && $model->validate())
        {
            $qrcode = $this->_app->qrcode;
            if($model->model == Qrcode::MODEL_TEM)
            {
                $model->scene_id = Qrcode::getSceneId();
                $result = $qrcode->temporary($model->scene_id,$model->expire_seconds);
                $model->expire_seconds = $result['expire_seconds']; // 有效秒数
            }
            else
            {
                $result = $qrcode->forever($model->scene_str);// 或者 $qrcode->forever("foo");
            }

            $model->ticket = $result['ticket'];
            $model->type = Qrcode::TYPE_SCENE;
            $model->url = $result['url']; // 二维码图片解析后的地址，开发者可根据该地址自行生成需要的二维码图片
            $model->save();

            return $this->redirect(['index']);
        }

        return $this->renderAjax('add', [
            'model'     => $model,
        ]);
    }

    /**
     * 验证表单
     *
     * @return array
     */
    public function actionValidateForm()
    {
        $model = new Qrcode();
        $model->load(Yii::$app->request->post());

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return \yii\widgets\ActiveForm::validate($model);
    }

    /**
     * ajax编辑
     *
     * @return string|yii\web\Response
     */
    public function actionEdit()
    {
        $id = Yii::$app->request->get('id');
        $model = Qrcode::findOne($id);

        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['index']);
        }

        return $this->renderAjax('edit', [
            'model'     => $model,
        ]);
    }


    /**
     * 删除全部过期的二维码
     *
     * @return mixed
     */
    public function actionDeleteAll()
    {
        if(Qrcode::deleteAll(['and',['model'=>Qrcode::MODEL_TEM],['<', 'end_time', time()]]))
        {
            return $this->message("删除成功",$this->redirect(['index']));
        }
        else
        {
            return $this->message("删除失败",$this->redirect(['index']),'error');
        }
    }

    /**
     * 删除二维码
     *
     * @param $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        if(Qrcode::findOne($id)->delete())
        {
            return $this->message("删除成功",$this->redirect(['index']));
        }
        else
        {
            return $this->message("删除失败",$this->redirect(['index']),'error');
        }
    }

    /**
     * 下载二维码
     */
    public function actionDownQr()
    {
        $id = Yii::$app->request->get('id');
        $model = Qrcode::findOne($id);

        $qrcode = $this->_app->qrcode;
        $url = $qrcode->url($model['ticket']);
        header("Cache-control:private");
        header('content-type:image/jpeg');
        header('content-disposition: attachment;filename="'.$model['name'].'_'.time().'.jpg"');
        readfile($url);
    }

    /**
     * 长链接二维码
     *
     * @return string
     */
    public function actionLongQr()
    {
        return $this->render('long-qr', [
        ]);
    }

    /**
     * 长链接转短连接
     */
    public function actionTransform()
    {
        $result = $this->setResult();
        $result->message = '二维码转化失败';

        $postUrl = Yii::$app->request->post('shortUrl','');
        // 长链接转短链接
        $url = $this->_app->url;
        try
        {
            $shortUrl  = $url->shorten($postUrl);
            if($shortUrl['errcode'] == 0)
            {
                $result->code = 200;
                $result->message = '二维码转化成功';
                $result->data = [
                    'short_url' => $shortUrl['short_url']
                ];
            }
        }
        catch (\Exception $e)
        {
            $result->message = $e->getMessage();
        }

        return $this->getResult();
    }

    /**
     * 二维码转换
     */
    public function actionQr()
    {
        $getUrl = Yii::$app->request->get('shortUrl',Yii::$app->request->hostInfo);

        $qr = Yii::$app->get('qr');
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', $qr->getContentType());

        return $qr->setText($getUrl)
            ->setSize(150)
            ->setMargin(7)
            ->writeString();
    }
}
