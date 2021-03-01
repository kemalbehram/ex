<?php
namespace backend\controllers;

use common\models\Votes;


use Yii;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use common\models\ApiAccessToken;
use linslin\yii2\curl;
/**
 *场外交易控制器
 *
 * Class MemberController
 * @package backend\modules\member\controllers
 */
class VoteController extends MController{
  
    /**
     * 币种列表
     *
     */
    public function actionList()
    {
                 
    $request = Yii::$app->request;       
    $tablePrefix = Yii::$app->db->tablePrefix;
  
    $data = (new \yii\db\Query())
            ->select('*')
            ->from("{$tablePrefix}vote ");

          $pages  = new Pagination(['totalCount' =>$data->count(), 'pageSize' =>$this->_pageSize]);
          $models = $data->orderBy('id desc')                               
                         ->limit($pages->limit)
                         ->where(['status'=>1])
                         ->all();

          return $this->render('list', [
              'models'           => $models,
                 'Pagination'       => $pages,
                
          ]);

    }

    public function actionDelete($id){
            $tablePrefix = Yii::$app->db->tablePrefix;
            $update = Yii::$app->db->createCommand()->update("{$tablePrefix}vote", 
              array(
                'status' => 0, 
              ),
              "id=".$id
            )->execute();    
            return $this->message("删除成功",$this->redirect(['list'])); 
    }

    public function actionFindVotesModel($id){
        if(empty($id)){
            return new Votes();
        }
        if(empty($model = Votes::findOne($id))){
            return new Votes();
        }
        return $model;
    }

    public function actionEdit(){

        $request = Yii::$app->request;

        $id  =  htmlspecialchars($request->get('id'));

        $model = $this->actionFindVotesModel($id);


        if($model->load(Yii::$app->request->post())) {


            //$id = $_POST['Votes']['id'];
       
            $model->title = $_POST['Votes']['title'];
            $model->coin_symbol = $_POST['Votes']['coin_symbol'];
            $model->coin_name = $_POST['Votes']['coin_name'];
            $model->introduce =$_POST['Votes']['introduce'];
            $model->coin_duihuan_money = $_POST['Votes']['coin_duihuan_money'];
            $model->coin_num = $_POST['Votes']['coin_num'];
            $model->coin_duihuan_num = $_POST['Votes']['coin_duihuan_num'];
            $model->coin_duihuan_min = $_POST['Votes']['coin_duihuan_min'];
            $model->coin_duihuan_max = $_POST['Votes']['coin_duihuan_max'];
            $model->start_time = strtotime($_POST['Votes']['start_time']);
            $model->end_time = strtotime($_POST['Votes']['end_time']);
            $model->img_small = $_POST['Votes']['img_small'];
            $model->img_big = $_POST['Votes']['img_big'];
            $model->info = $_POST['Votes']['info'];


            if ($model->save()) {
                return $this->redirect(['list']);
            }
        }
        return $this->render('edit',[
            'model' => $model,
        ]);
    }
}