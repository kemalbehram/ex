<?php

namespace backend\controllers;

use Yii;
use common\models\SysPush;
use yii\web\NotFoundHttpException;


class SyspushController extends MController{
    public $TYPE = [
        0 => '公告URL推送',
        1 => '行情推送'
    ];
    public $STATUS = [
       -1 => '推送失败',        
        0 => '等待推送',
        1 => '推送成功'
    ];
    public $STATUS_COLOR = [
        -1 => 'red',
        0 => 'black',
        1 => '#1ab394',
    ];
    public function actionIndex(){

        $data = SysPush::find()->all();
        return $this->render('index', [
            'data' => $data,
            'status'  => $this->STATUS,
            'type'  => $this->TYPE,
            'status_color' => $this->STATUS_COLOR
        ]);
    }

    /**
     * @return string
     */
    public function actionEnable(){
        $request = Yii::$app->request;
        $id      = $request->post('id');
        $status  = $request->post('status');
        $model   = $this->findModel($id);
        $model->status = $status;
        if($model->save()){
            return json_encode(['code' => 200, 'message' => '操作成功']);
        }else{
            return json_encode(['code' => 201, 'message' => '操作失败']);
        }
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionEdit(){
        $request = Yii::$app->request;
        $id      = $request->get('id');
        $model   = $this->findModel($id);
        if($model->load(Yii::$app->request->post())) {
            $model->add_time = time();
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('edit', [
            'model' => $model,
            'type'  => $this->TYPE,
        ]);
    }

    /**
     * Deletes an existing Coins model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if($this->findModel($id)->delete())
        {
            return $this->message("删除成功",$this->redirect(['index']));
        }
        else
        {
            return $this->message("删除失败",$this->redirect(['index']),'error');
        }
    }

    /**
     * Finds the Coins model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Coins the loaded model
     */
    protected function findModel($id)
    {
        if (empty($id)) {
            return new SysPush();
        }

        if (empty($model = SysPush::findOne($id))) {
            return new SysPush();
        }

        return $model;
    }


    public function actionPush($id)
    {
        $app_key = \Yii::$app->config->info("JPUSH_APPKEY");
        $master_secret = \Yii::$app->config->info("JPUSH_SECRET");
        //$client = new JPush($app_key, $master_secret);

        if (empty($model = SysPush::findOne($id))) {
            return $this->message("操作失败,内容不存在",$this->redirect(['index']),'error');
        }

        //var_dump($model);die();

        $title = $model->title;
        $content = $model->object;
        $type = $model->type;
        $url = $model->extra;

        $msg = array(
            'title' => $title,
            'extras' => array(
                'type' => $type,
                'content' => $content,
                'url' => $url,
            ),
        );

        $client = new \JPush\Client($app_key, $master_secret);



        $pusher = $client->push();
        $pusher->setPlatform('all');
        $pusher->options(array(
                    // apns_production: 表示APNs是否生产环境，
                    // True 表示推送生产环境，False 表示要推送开发环境；如果不指定则默认为推送生产环境
                    'apns_production' => false,//APP_DEBUG ? false : true,
                ));

        $pusher->addAllAudience();
        
        $pusher->androidNotification($content, $msg);

        $msg['alert'] = $title;
        unset($msg['title']);
        $pusher->iosNotification(['title'=>$title,'body'=>$content], $msg);
  
        try {
            $pusher->send();
            $model->status = 1;
            $model->save();
            return $this->message("操作成功",$this->redirect(['index']));

        } catch (\JPush\Exceptions\JPushException $e) {
            // try something else here
            //print $e;
            $model->status = -1;
            $model->save();            
            return $this->message("操作失败",$this->redirect(['index']),'error');


        }
    }
}
