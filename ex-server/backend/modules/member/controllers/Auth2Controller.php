<?php
/**
 * Created by PhpStorm.
 * User: landehua
 * Date: 2018/5/30 0030
 * Time: 11:22
 */

namespace backend\modules\member\controllers;

use common\models\member\Member;
use common\models\MemberVerified;
use Yii;
use yii\data\Pagination;
use common\models\MemberWealthOrder;
use common\models\MemberWealthPackage;

class Auth2Controller extends UController{
    public $STATUS = [
        0 => '未提交',
        2 => '待审核',
        1 => '认证通过',
        -1 => '认证失败',
    ];
    public $STATUS_COLOR = [
        0 => '#aaa',
        2 => '#f7a54a',
        1 => '#1ab394',
        -1 => 'red',
    ];
    public function actionIndex(){

        $request  = Yii::$app->request;
        $type     = $request->get('type',1);
        $keyword  = $request->get('keyword','');

        switch ($type) {
            case '1':
                $where = ['like', 'mobile_phone', $keyword];
                break;
            case '2':
                $where = ['like', 'email', $keyword];
                break;
            default:
                $where = [];
                break;
        }

        $data = Member::find()->where(['<>', 'bank_status', 0])->andWhere($where);
        $pages  = new Pagination(['totalCount' =>$data->count(), 'pageSize' =>$this->_pageSize]);
        $models = $data->offset($pages->offset)
            ->orderBy('id DESC')
            ->limit($pages->limit)
            ->all();

        return $this->render('index',[
            'models'  => $models,
            'Pagination' => $pages,
            'type'    => $type,
            'keyword' => $keyword,
            'status'  => $this->STATUS,
            'status_color' => $this->STATUS_COLOR
        ]);
    }

    public function actionExamine(){
        $request = Yii::$app->request;
        $id = $request->post('id');
        $type = $request->post('type');
        if(empty($id) || !in_array($type, ['fail', 'success'])){
            return json_encode(['code' => 201, 'message' => '缺少参数']);
        }
        if($type == 'fail'){
            $status = -1;
        }else{
            $status = 1;
        }
        $model = Member::findOne(['id' => $id]);
        if(in_array($model->status, [-1,1])){
            return json_encode(['code' => 201, 'message' => '已操作过，不能再进行操作']);
        }
        $model->bank_status = $status;
        if($status == 1){
            $model->verified_level = 3;
        }
        if($model->save()){
            return json_encode(['code' => 200, 'message' => '操作成功']);
        }else{
            return json_encode(['code' => 201, 'message' => '操作失败']);
        }
    }


}