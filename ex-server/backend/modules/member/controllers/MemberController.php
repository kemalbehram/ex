<?php
namespace backend\modules\member\controllers;

use Yii;
use yii\data\Pagination;
use common\models\member\Member;
use api\models\MemberWallet;
use common\models\BalanceLog;
use common\models\MemberVerified;
use common\models\MemberWealthOrder;
use common\models\MemberWealthPackage;
use common\models\MemberWealthBalance;
use common\jinglan\Trade;
use jinglan\ves\VesRPC;
use common\jinglan\CreateWallet;
use yii\web\NotFoundHttpException;
use yii\helpers\Url;
use yii\web\Session;
use common\jinglan\DSActivity;

/**
 * 用户控制器
 *
 * Class MemberController
 * @package backend\modules\member\controllers
 */
class MemberController extends UController{
    public $VERIFIED_STATUS = [
        0 => '未认证',
        1 => '已认证'
    ];
    public $STATUS_COLOR = [
        0 => '#aaa',
        1 => '#1ab394',
    ];

    // 交易类型
    protected $transaction_type = [
        0  => '<i class="fa fa-warning" style="color:#FFC157" title="未定义"></i>',//未定义
        1  => '<span style="color:#28A745"><i class="fa fa-plus-circle fa-fw"></i>充值</span>',
        10 => '<span style="color:#DC3545"><i class="fa fa-minus-circle fa-fw"></i>转出</span>',
    ];
    // 交易描述
    protected $detial_type = [
        'default'      => '<i class="fa fa-warning" style="color:#FFC157" title="未定义"></i>',//未定义
        'exchange'     => '交易所',
        'chain'        => '链上',
        'system'       => '系统',
        'withdraw_fee' => '提现手续费',
        'otc'          => '法币交易',
        'withdraw'     => '转出申请',
    ];
    /**
     * 首页
     */
    public function actionIndex()
    {

        $request  = Yii::$app->request;
        $type     = $request->get('type',2);
        $keyword  = $request->get('keyword','');
        $starttime  = $request->get('starttime')?: '';
        $endtime  = $request->get('endtime')?: '';
        $starttime  = strtotime($starttime);
        $endtime  = strtotime($endtime);

        // 关联角色查询
       $data   = Member::find()->with(['verified']);
       $where[]='and';
        if (!empty($starttime)) {
            $where[]=array('>','jl_member.created_at',$starttime);
        }
        if (!empty($endtime)) {
            $where[]=array('<','jl_member.created_at',$endtime);
        }
        if (!empty($keyword)) {
            if($type == 1){
                $where[]=array('=','jl_member.id',$keyword);
            }elseif($type == 2){
                $where[]=array('=','jl_member.nickname',$keyword);
            }elseif($type == 3){
                $where[]=array('=','jl_member.mobile_phone',$keyword);
            }
        }
        $data->where($where);
        $count = $data->count();
        $pagination = new Pagination(['totalCount' => $count]);
       $models = $data->offset($pagination->offset)
           ->leftJoin('jl_member_verified', 'jl_member.id = jl_member_verified.uid')
           ->orderBy('type desc,created_at desc')
           ->limit($pagination->limit)
           ->all();

        $all_num = Member::find()->where($where)->select(['sum(jl_member.type) as all_num'])
            ->leftJoin('jl_member_verified', 'jl_member.id = jl_member_verified.uid')
            ->asArray()
            ->one();
        if (empty($all_num)) {
            $all_num["all_num"] = '0';
        }

       return $this->render('index',[
           'models'  => $models,
           'pagination'   => $pagination,
           'type'    => $type,
           'keyword' => $keyword,
           'status'  => $this->VERIFIED_STATUS,
            'all_num' => $all_num["all_num"],
           'status_color' => $this->STATUS_COLOR,
       ]);

    }



    /**
     * 编辑/新增
     *
     * @return string|yii\web\Response
     * @throws yii\base\Exception
     */
    public function actionChange_uinfo()
    {
        $request  = Yii::$app->request;
        $id       = $request->get('uid');
        $model    = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['index']);
        }

        return $this->render('change_uinfo', [
            'model' => $model,
        ]);
    }








    /**
     * 编辑/新增
     *
     * @return string|yii\web\Response
     * @throws yii\base\Exception
     */
    public function actionEdit()
    {
        $request  = Yii::$app->request;
        $id       = $request->get('id');
        $model    = $this->findModel($id);

        $pass     = $model->password_hash;// 原密码
        if ($model->load(Yii::$app->request->post()))
        {
            // 验证密码是否修改
            if($model->password_hash != $pass)
            {
                $model->password_hash = Yii::$app->security->generatePasswordHash($model->password_hash);
            }

            // 提交创建
            if($model->save())
            {
                return $this->redirect(['index']);
            }
        }

        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    /**
     * 删除
     *
     * @param $id
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @throws yii\db\StaleObjectException
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
     * 修改个人资料
     *
     * @return string|yii\web\Response
     */
    public function actionPersonal()
    {
        $request  = Yii::$app->request;
        $id       = $request->get('id');
        $model    = $this->findModel($id);

        // 提交表单
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            return $this->redirect(['index']);
        }

        return $this->render('personal', [
            'model' => $model,
        ]);
    }

    /**
     * 返回模型
     *
     * @param $id
     * @return Member|null|static
     */
    protected function findModel($id)
    {
        if (empty($id))
        {
            return new Member;
        }

        if (empty($model = Member::findOne($id)))
        {
            return new Member;
        }

        return $model;
    }
    public function actionQuery($id)
    {    
        return json_encode(['code'=>200,'data'=>$balance_all]);
    }

    // 用户信息详情页
    public function actionUserDetail()
    {
        $request  = Yii::$app->request;
        $id       = $request->get('id');

        // 1用户基本信息
        $user = Member::find()->where(['id' => $id])->one();


        // 2用户资产信息
        $_POST['chain_network'] = 'main_network';
        // $_POST['chain_network'] = 'testnet';
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($id) ?: [];// 成功返回数据，失败返回false

        // 交易记录信息
        // 判断是否有搜索条件
        $key_type   = $request->get('key_type');
        $keyword    = $request->get('keyword');
        $query      = BalanceLog::find()->where(['member_id' => $id])->andWhere(['network' => 0])->orderBy('id DESC');
        // $query      = BalanceLog::find()->where(['member_id' => $id])->andWhere(['network' => 1]);
        if (!empty($key_type) && !empty($keyword)) {
            $query->andWhere([$key_type => $keyword]);
        }
        $count      = $query->count();
        $pagination = new Pagination(['totalCount' => $count]);
        $pagination->setPageSize(10);
        $balance_logs = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('user-detail', [
            'user'             => $user,
            // 'wallets'          => $wallets,
            'balance_logs'     => $balance_logs,
            'balance_all'      => $balance_all[0],
            'transaction_type' => $this->transaction_type,
            'detial_type'      => $this->detial_type,
            'pages'            => $pagination,
        ]);
    }


    // 锁仓页面
    public function actionCoinlock()
    {
        $request  = Yii::$app->request;
        $id       = $request->get('uid');
        $coin_symbol       = $request->get('coin_symbol');

        // 1用户基本信息
        $user = Member::find()->where(['id' => $id])->asArray()->one();


        // 2用户资产信息
        $_POST['chain_network'] = 'main_network';
        // $_POST['chain_network'] = 'testnet';
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($id) ?: [];// 成功返回数据，失败返回false

        foreach ($balance_all[0] as $key => $value) {
            if ($value['name'] == $coin_symbol) {
                $balance = $value['available'];
            }
        }
        $wealth_package = (new \yii\db\Query())->from('jl_member_wealth_package')->where(['status' => 1,'coin_symbol'=>$coin_symbol,'type'=>4])->all();
        if(empty($wealth_package)){
          $wealth_package = [];
        }

        return $this->render('coinlock', [
            'user'             => $user,
            'balance'      => $balance,
            'wealth_package'      => $wealth_package,
        ]);
    }



    // 锁仓页面
    public function actionCoinlock_ledi()
    {
        $request  = Yii::$app->request;
        $id       = $request->get('uid');
        $coin_symbol       = $request->get('coin_symbol');

        // 1用户基本信息
        $user = Member::find()->where(['id' => $id])->asArray()->one();


        // 2用户资产信息
        $_POST['chain_network'] = 'main_network';
        // $_POST['chain_network'] = 'testnet';
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($id) ?: [];// 成功返回数据，失败返回false

        foreach ($balance_all[0] as $key => $value) {
            if ($value['name'] == $coin_symbol) {
                $balance = $value['available'];
            }
        }
        $wealth_package = (new \yii\db\Query())->from('jl_member_wealth_package')->where(['status' => 1,'coin_symbol'=>$coin_symbol,'type'=>4])->all();
        if(empty($wealth_package)){
          $wealth_package = [];
        }

        $rpc = new VesRPC();
        $rpc_ret = $rpc->do_rpc("market.last", ['LDGCUSDT']);
        if ($rpc_ret['code'] == 0) {
            $ldgc_price = 0;
        }else{
            $ldgc_price = $rpc_ret['data'];
        }

        return $this->render('coinlock_ledi', [
            'user'             => $user,
            'balance'      => $balance,
            'wealth_package'      => $wealth_package,
            'ldgc_price'      => $ldgc_price,
        ]);
    }






    //递归查询所有下线的总入金
    public  function getLocknum($pid='0',$uid=0,$ceng=0)
    {
        //查询当前用户的下一级用户
        $t=Member::find();
        $wherea['last_member']=$pid;
        $list=$t->where($wherea)->orderBy('id asc')->asArray()->all();

        //统计下一级用户的总入金
        if(is_array($list)){
            $all_rujin_count = 0;
            foreach($list as $k => $v)
            {
                    $usdt_num = MemberWealthOrder::find()->where(['uid'=>$v['id'],'coin_symbol'=>'USDT','type'=>4])->andFilterWhere(['like','log','手动锁仓'])->select('sum(amount) as amount')->orderBy('ctime DESC')->limit(50)->asArray()->one();
                    if (empty($usdt_num['amount'])) {
                        $usdt_num = 0;
                    }else{
                        $usdt_num = $usdt_num['amount'];
                    }

                    //查询这个用户的下级入金加到总数
                    $all_rujin_count = $all_rujin_count + $usdt_num + $this->getLocknum($v['id']);

                    
            }
            return $all_rujin_count;
        }
    }







    // 所有锁仓订单
    public function actionCoinlock_order()
    {

        // 获取查询条件
        $request  = Yii::$app->request;
        $keyword  = $request->get('keyword')?: '';
        $key_type = $request->get('key_type')?: '';

        $query      = MemberWealthOrder::find()->where(['jl_member_wealth_order.type'=>4])->orderBy('jl_member_wealth_order.id DESC');
        // $query      = BalanceLog::find()->where(['member_id' => $id])->andWhere(['network' => 1]);
        // 
        $tongji = '';
        if (!empty($key_type) && !empty($keyword)) {
            $query->andWhere([$key_type => $keyword]);
            $tongji = '团队总计入金：'.$this->getLocknum($keyword);

        }

        $count      = $query->count();
        $pagination = new Pagination(['totalCount' => $count]);
        $pagination->setPageSize(15);
        $wealth_package_order = $query->offset($pagination->offset)
            ->select(['b.*','jl_member_wealth_order.*'])
            ->leftJoin('jl_member b', 'jl_member_wealth_order.uid = b.id')
            ->limit($pagination->limit)
            ->asArray()
            ->all();

//print_r($wealth_package_order);
        // $wealth_package_order = MemberWealthOrder::find()
        //                         ->leftJoin('jl_member', 'jl_member_wealth_order.uid = jl_member.id')
        //                         ->where(['jl_member_wealth_order.type'=>4])
        //                         ->orderBy('jl_member_wealth_order.ctime DESC')
        //                         ->limit(50)
        //                         ->asArray()
        //                         ->all();
        if(empty($wealth_package_order)){
          $wealth_package_order = [];
        }

        return $this->render('coinlock_order', [
            'data'      => $wealth_package_order,
            'tongji'      => $tongji,
            'pagination'            => $pagination,
        ]);

    }

    // 用户信息详情页
    public function actionCoinlock_post()
    {
        $request  = Yii::$app->request;
        $uid =  $request->post('uid');
        $id       = $request->post('wealth_package_id');
        $num       = floatval($request->post('amount'));
        $coin_symbol       = $request->post('coin_symbol');


        if(empty($id)){
          return $this->message("请选择锁仓套餐",$this->redirect(['coinlock', 'uid'=>$uid, 'coin_symbol'=>$coin_symbol]),'error');
        }
        if(empty($num)){
          return $this->message("请输入锁仓数量",$this->redirect(['coinlock', 'uid'=>$uid, 'coin_symbol'=>$coin_symbol]),'error');
        }
        $package_info = MemberWealthPackage::find()->where(['status'=>1,'id'=>$id])->select('id,type,coin_symbol,name,period,min_num,day_profit')->orderBy('ctime DESC')->limit(50)->asArray()->one();
        if(empty($package_info)){
          return $this->message("套餐设置有误",$this->redirect(['coinlock', 'uid'=>$uid, 'coin_symbol'=>$coin_symbol]),'error');
        }

        if($num < $package_info['min_num']){
          return $this->message('锁仓金额过低,最低'.sprintf('%.2f',$package_info['min_num']),$this->redirect(['coinlock', 'uid'=>$uid, 'coin_symbol'=>$coin_symbol]),'error');
        }
        $coin_symbol = $package_info['coin_symbol'];

//print_r($uid);die();
        // 获取用户资产
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($uid);// 成功返回数据，失败返回false
        if (!$balance_all) {
            $this->error_message('_The_application_process_failed_unexpectedly_Please_try_again_later_');
        }

        foreach ($balance_all[0] as $key => $value) {
            if ($value['name'] == $coin_symbol) {
                if ($value['available'] < $num) {
                    return $this->message("可用 $coin_symbol 余额不足",$this->redirect(['coinlock', 'uid'=>$uid, 'coin_symbol'=>$coin_symbol]),'error');
                }
                break;
            }
        }
        if($package_info['type'] == 4){
            $memo = "锁仓 ";
        }else{
            $memo = "购买矿机花费 ";
        }

        //扣费
        $tablePrefix = Yii::$app->db->tablePrefix;
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_balance", [ 
            'uid' => $uid,
            'uid2' => 0,
            'amount' => -$num ,
            'coin_symbol' => $coin_symbol,
            'memo' => $memo.$num.' '.$coin_symbol,
            'ctime' => time(),
        ])->execute();
        $id = Yii::$app->db->getLastInsertID();
        if (!$id) {
            return $this->message("系统繁忙请稍后重试",$this->redirect(['coinlock', 'uid'=>$uid, 'coin_symbol'=>$coin_symbol]),'error');
        }


        //活期且已经买过直接加订单数量，写log,返回。定期直接加记录
        // if ($package_info['type'] == 2) {
        //     $where = [
        //         'and',
        //         ['=', 'status', 1],
        //         ['=', 'type', 2],
        //         ['=', 'uid', $uid],
        //     ];
        //     $order_model = MemberWealthOrder::find()->orderBy('id desc')->where($where)->one();
        //     if (!empty($order_model)){
        //         $release_log_str = $order_model->log.'(时间'.date('Y-m-d H:i:s', time()).',追加购买:'.$num.$coin_symbol.',购买后余额'.($order_model->amount + $num).')--';
        //         $order_model->amount = $order_model->amount + $num;
        //         $order_model->log = $release_log_str;
        //         $order_model->save(false);
        //         return $this->message("购买成功",$this->redirect(['user-detail', 'id'=>$uid]),'success');
        //         $this->success_message('购买成功');
        //         die();
        //     }
        // }

        //写记录
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_order", [
            'uid' => $uid,
            'type' => $package_info['type'],
            'order_id' => $id,
            'wealth_pid' => $package_info['id'],
            'name' => $package_info['name'],
            'period' => $package_info['period'],
            'day_profit' => $package_info['day_profit'],
            'surplus_period' => $package_info['period'],
            'status' => 1,
            'amount' => $num ,
            'coin_symbol' => $coin_symbol,
            'ctime' => time(),
            'last_allocation' => time(),
            'log' => '手动锁仓',
        ])->execute();
        $id = Yii::$app->db->getLastInsertID();
        //返上级
        $this->parentReward($uid,$num);

        return $this->message("锁仓成功！",$this->redirect(['user-detail', 'id'=>$uid]),'success');
    }



    // 用户信息详情页
    public function actionCoinlock_post_ledi()
    {
        $request  = Yii::$app->request;
        $uid =  $request->post('uid');
        $id       = $request->post('wealth_package_id');
        //平台币加的锁仓
        $num       = floatval($request->post('ldgc_num'));
        //USDT自己锁仓
        $usdt_num       = floatval($request->post('usdt_num'));
        $coin_symbol       = 'LDGC';


        if(empty($id)){
          return $this->message("请选择锁仓套餐",$this->redirect(['coinlock_ledi', 'uid'=>$uid, 'coin_symbol'=>'USDT']),'error');
        }
        if(empty($num)){
          return $this->message("请输入锁仓数量",$this->redirect(['coinlock_ledi', 'uid'=>$uid, 'coin_symbol'=>'USDT']),'error');
        }
        $package_info = MemberWealthPackage::find()->where(['status'=>1,'id'=>$id])->select('id,type,coin_symbol,name,period,min_num,day_profit')->orderBy('ctime DESC')->limit(50)->asArray()->one();
        if(empty($package_info)){
          return $this->message("套餐设置有误",$this->redirect(['coinlock_ledi', 'uid'=>$uid, 'coin_symbol'=>'USDT']),'error');
        }

        if($num < $package_info['min_num']){
          return $this->message('锁仓金额过低,最低'.sprintf('%.2f',$package_info['min_num']),$this->redirect(['coinlock_ledi', 'uid'=>$uid, 'coin_symbol'=>'USDT']),'error');
        }

        if($package_info['type'] == 4){
            $memo = "锁仓 ";
        }else{
            $memo = "购买矿机花费 ";
        }

        //写记录
        $tablePrefix = Yii::$app->db->tablePrefix;
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_order", [
            'uid' => $uid,
            'type' => $package_info['type'],
            'order_id' => $id,
            'wealth_pid' => $package_info['id'],
            'name' => '锁仓1年',
            'period' => 365,
            'day_profit' => $package_info['day_profit'],
            'surplus_period' => 365,
            'status' => 1,
            'amount' => $usdt_num ,
            'coin_symbol' => 'USDT',
            'ctime' => time(),
            'last_allocation' => time()+365*24*3600,
            'log' => '手动锁仓',
        ])->execute();

        //写记录
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_order", [
            'uid' => $uid,
            'type' => $package_info['type'],
            'order_id' => $id,
            'wealth_pid' => $package_info['id'],
            'name' => $package_info['name'],
            'period' => $package_info['period'],
            'day_profit' => $package_info['day_profit'],
            'surplus_period' => $package_info['period'],
            'status' => 1,
            'amount' => $num ,
            'coin_symbol' => $coin_symbol,
            'ctime' => time(),
            'last_allocation' => time(),
            'log' => '手动锁仓',
        ])->execute();
        $id = Yii::$app->db->getLastInsertID();
        //返上级
        $this->parentReward($uid,$usdt_num);

        return $this->message("锁仓成功！",$this->redirect(['user-detail', 'id'=>$uid]),'success');
    }



    // 用户信息详情页
    public function actionCoinlock_ledi_release()
    {
        $request  = Yii::$app->request;
        $uid =  $request->get('uid');

        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $start = mktime(0,0,0,$month,$day,$year);//当天开始时间戳
        $end= mktime(23,59,59,$month,$day,$year);//当天结束时间戳
        $where = [
            'and',
            ['=', 'status', 1],
            ['=', 'uid', $uid],
            ['>', 'ctime', $start],
            ['<', 'ctime', $end],
        ];
        $query = MemberWealthOrder::find();
        $data = $query->where($where)->select('*')->orderBy('ctime DESC')->asArray()->all();

        if (empty($data)){
          return $this->message("今日无业绩",$this->redirect(['user-detail', 'id'=>$uid]),'error');
        }


        $count = 0;
        foreach ($data as $k => &$x){
            $day_profit = $x['day_profit'];
            $order_amount = $x['amount'];
            $coin_symbol = 'LDGC';
            $uid = $x['uid'];


            $revenue = $order_amount / 10;

            $release_log_str = $x['log'].'(时间'.date('Y-m-d H:i:s', time()).', 经理额外释放当天新增业绩的10%, 获得收益:'.$revenue.')--';
            //更新订单状态
            $order_model = MemberWealthOrder::find()->where(['id'=>$x['id']])->one();
            $order_model->revenue = $order_model->revenue + $revenue;
            $order_model->get_profit = $order_model->get_profit + $revenue;
            //$order_model->surplus_period = $order_model->surplus_period -1;
            $order_model->last_allocation = time();
            $order_model->log = $release_log_str;
            $order_model->save(false);

            //加收益
            $tablePrefix = Yii::$app->db->tablePrefix;
            Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_balance", [ 
                'uid' => $uid,
                'uid2' => 0,
                'amount' => $revenue ,
                'coin_symbol' => $coin_symbol,
                'memo' => '释放当天业绩收益,数量: '.$revenue.' '.$coin_symbol,
                'ctime' => time(),
            ])->execute();
            $id = Yii::$app->db->getLastInsertID();


            //给上级返利
            //$this->rebate($uid,$revenue,$coin_symbol);
            $count += 1;
        }



        return $this->message("释放今日业绩成功！",$this->redirect(['user-detail', 'id'=>$uid]),'success');
    }



    //返上级奖励上级奖励
    private function parentReward($uid,$amount){
        $level1_reward = 1;
        $level2_reward = 0.5;
        $level3_reward = 0.2;

        $user = Member::find()->where(['id'=>$uid])->one();

        // //自己锁仓
        // $package_info = MemberWealthPackage::find()->where(['id'=>3])->orderBy('ctime DESC')->asArray()->one();
        // if(empty($package_info)){
        //   //echo '套餐不存在';
        //   return;
        // }
        // //写记录
        // $tablePrefix = Yii::$app->db->tablePrefix;
        // Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_order", [
        //     'uid' => $uid,
        //     'type' => 4,
        //     'order_id' => 0,
        //     'wealth_pid' => $package_info['id'],
        //     'name' => $package_info['name'],
        //     'period' => $package_info['period'],
        //     'day_profit' => $package_info['day_profit'],
        //     'surplus_period' => $package_info['period'],
        //     'status' => 1,
        //     'amount' => $amount ,
        //     'coin_symbol' => 'USDT',
        //     'ctime' => time(),
        //     'last_allocation' => time(),
        //     'log' => '推荐用户奖励锁仓',
        // ])->execute();
        // $id = Yii::$app->db->getLastInsertID();
        // //echo "锁仓成功<br />";


        if (empty($user['last_member'])) {
            //echo "没有上级";
            return;
        }

        //1代
        $parent_user = Member::find()->where(['id'=>$user['last_member']])->one();
        if(!empty($parent_user)){
            //echo "给上1级用户".$parent_user['id'].'-'.$parent_user['username'].'返锁仓套餐<br />';
            $this->add_coinlock_order($parent_user,$amount * $level1_reward,$user);

            //2代
            if(!empty($parent_user['last_member'])){
                $parent_user2 = Member::find()->where(['id'=>$parent_user['last_member']])->one();
                if(!empty($parent_user2)){
                    //echo "给上2级用户".$parent_user2['id'].'-'.$parent_user2['username'].'返锁仓套餐<br />';
                    $this->add_coinlock_order($parent_user2,$amount * $level2_reward,$user);

                    //3代
                    if(!empty($parent_user2['last_member'])){
                        $parent_user3 = Member::find()->where(['id'=>$parent_user2['last_member']])->one();
                        if(!empty($parent_user3)){
                            //echo "给上3级用户".$parent_user3['id'].'-'.$parent_user3['username'].'返锁仓套餐<br />';
                            $this->add_coinlock_order($parent_user3,$amount * $level3_reward,$user);
                        }  
                    }

                }                         
            }                 
        }      
    
    }
  public function add_coinlock_order($uinfo,$num,$user){
        $package_info = MemberWealthPackage::find()->where(['id'=>6])->orderBy('ctime DESC')->asArray()->one();
        if(empty($package_info)){
          //echo '套餐不存在';
          return;
        }

        //写记录
        $tablePrefix = Yii::$app->db->tablePrefix;
        Yii::$app->db->createCommand()->insert("{$tablePrefix}member_wealth_order", [
            'uid' => $uinfo['id'],
            'uid2' => $user['id'],
            'type' => 4,
            'order_id' => 0,
            'wealth_pid' => $package_info['id'],
            'name' => $package_info['name'],
            'period' => $package_info['period'],
            'day_profit' => $package_info['day_profit'],
            'surplus_period' => $package_info['period'],
            'status' => 1,
            'amount' => $num ,
            'coin_symbol' => 'USDT',
            'ctime' => time(),
            'last_allocation' => time(),
            'log' => '推荐用户奖励锁仓',
        ])->execute();
        $id = Yii::$app->db->getLastInsertID();
  }


    // 锁仓收益
    public function actionCoinlock_profit()
    {
        $request  = Yii::$app->request;
        $type     = $request->get('type',1);
        $keyword  = $request->get('keyword','');
        $starttime  = $request->get('starttime')?: '';
        $endtime  = $request->get('endtime')?: '';
        $starttime  = strtotime($starttime);
        $endtime  = strtotime($endtime);

        // 关联角色查询
        $data   = MemberWealthBalance::find();
        $where[]='and';
        if (!empty($starttime)) {
            $where[]=array('>','jl_member_wealth_balance.ctime',$starttime);
        }
        if (!empty($endtime)) {
            $where[]=array('<','jl_member_wealth_balance.ctime',$endtime);
        }
        if (!empty($keyword)) {
                $where[]=array('=','jl_member_wealth_balance.uid',$keyword);
        }
        $where[]=array('>','jl_member_wealth_balance.amount',0);
        $data->where($where);
        //总数
        $num = $data->select('sum(amount) as all_amount')->asArray()->one();
        if (empty($num['all_amount'])) {
            $all_amount = 0;
        }else{
            $all_amount = round($num['all_amount'],4);
        }

        //记录
        $count = $data->count();
        $pagination = new Pagination(['totalCount' => $count,'pageSize' =>20]);
        $models = $data->offset($pagination->offset)
           ->select('*')
           ->orderBy('id desc')
           ->limit($pagination->limit)
           ->asArray()
           ->all();


       return $this->render('coinlock_profit',[
           'models'  => $models,
           'pagination'   => $pagination,
           'type'    => $type,
           'keyword' => $keyword,
           'all_amount' => $all_amount,
           'status'  => $this->VERIFIED_STATUS,
           'status_color' => $this->STATUS_COLOR,
       ]);
    }





    // 用户信息详情页
    public function actionAguserDetail()
    {
        $request  = Yii::$app->request;
        $id       = $request->get('id');

        // 1用户基本信息
        $user = Member::find()->where(['id' => $id])->one();


        // 2用户资产信息
        $_POST['chain_network'] = 'main_network';
        // $_POST['chain_network'] = 'testnet';
        $_POST['return_way'] = 'array';
        $balance_all = Trade::balance_v2($id) ?: [];// 成功返回数据，失败返回false

        // 交易记录信息
        // 判断是否有搜索条件
        $key_type   = $request->get('key_type');
        $keyword    = $request->get('keyword');
        $query      = BalanceLog::find()->where(['member_id' => $id])->andWhere(['network' => 0])->orderBy('id DESC');
        // $query      = BalanceLog::find()->where(['member_id' => $id])->andWhere(['network' => 1]);
        if (!empty($key_type) && !empty($keyword)) {
            $query->andWhere([$key_type => $keyword]);
        }
        $count      = $query->count();
        $pagination = new Pagination(['totalCount' => $count]);
        $pagination->setPageSize(10);
        $balance_logs = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('aguser-detail', [
            'user'             => $user,
            // 'wallets'          => $wallets,
            'balance_logs'     => $balance_logs,
            'balance_all'      => $balance_all[0],
            'transaction_type' => $this->transaction_type,
            'detial_type'      => $this->detial_type,
            'pages'            => $pagination,
        ]);
    }




    // 后台手动更新账户币种资产
    public function actionUpdateBalance()
    {
        $request  = Yii::$app->request;

        // 获取参数信息
        $change_type = $request->post('change_type');
        $member_id   = $request->post('member_id');
        $coin        = $request->post('coin_name');
        $addr        = $request->post('addr');
        $value_dec   = $request->post('value');
        $memo   = $request->post('memo');

        if (empty($addr)){
            //return $this->message("请先生成地址！",$this->redirect(['user-detail', 'id'=>$member_id]),'error');
        }


        if (empty($change_type) || empty($member_id) || empty($coin) || empty($value_dec)) {
            return $this->message("参数不完整！",$this->redirect(['user-detail', 'id'=>$member_id]),'error');
        }

        // 充值金额合法性判断
        //$message_text = intval($change_type) == 1 ? '充币' : '扣币';
        if (floatval($value_dec) <= 0) {
            return $this->message("数量不合法！",$this->redirect(['user-detail', 'id'=>$member_id]),'error');
        }

        // 读取balance_log数据表获取用户银行当前资产
        $balance = BalanceLog::find()
            ->select(['balance', 'network'])
            ->where(['member_id' => intval($member_id)])
            ->andWhere(['coin_symbol' => $coin])
            ->orderBy('id DESC')
            ->one();
        if ($balance) {
            // 用户该地址在balance_log表中有记录
            $user_balance = $balance->balance;
            $network      = 0;
        }else{
            $user_balance = 0;
            $network      = 0;
        }

        if($change_type < 20){//加币  扣币
             $change = intval($change_type)==1 ? (float)$value_dec : -(float)$value_dec;
            // 数据库存储数据【balance_log表】
            $balance_log              = new BalanceLog();
            $balance_log->type        = intval($change_type);
            $balance_log->member_id   = intval($member_id);
            $balance_log->coin_symbol = $coin;
            $balance_log->addr        = $addr;
            $balance_log->change      = $change;
            $balance_log->balance     = (float)$user_balance + $change;
            $balance_log->fee         = 0;
            $balance_log->detial      = $member_id.'-'.time().'-'.$addr;
            $balance_log->detial_type = 'system';
            $balance_log->ctime       = time();
            $balance_log->network     = $network;
            $balance_log->memo     = $memo;

            if ($balance_log->save()) {
                return $this->message("更新用户资产成功！",$this->redirect(['user-detail', 'id'=>$member_id]),'success');
            }else{
                return $this->message("更新用户资产失败！",$this->redirect(['user-detail', 'id'=>$member_id]),'error');
            }           
        }else{//冻结 解冻

            //锁仓赠币
            if($change_type == 31){
                DSActivity::lock(intval($member_id),$coin,(float)$value_dec,intval($memo),30,1);
                return $this->message("添加用户锁仓信息成功！",$this->redirect(['user-detail', 'id'=>$member_id]),'success');
            }
                        
            $change = intval($change_type)==21 ? (float)$value_dec : -(float)$value_dec;

            $tablePrefix = Yii::$app->db->tablePrefix;

             Yii::$app->db->createCommand()->insert("{$tablePrefix}otc_merchants_freeze", [ 
            'uid' => intval($member_id),
            'amount' => $change ,
            'coin_symbol' => $coin,   
            'update_time' => time(),                                                
            ])->execute();
            $id = Yii::$app->db->getLastInsertID();   
            if ($id) {
                return $this->message("更新用户资产成功！",$this->redirect(['user-detail', 'id'=>$member_id]),'success');
            }else{
                return $this->message("更新用户资产失败！",$this->redirect(['user-detail', 'id'=>$member_id]),'error');
            }           
        }

    }

    // 生成地址
    public function actionGenerateAddress()
    {
        $request  = Yii::$app->request;
        $_POST['chain_network'] = 'main_network';
        // $_POST['chain_network'] = 'testnet';
        $_POST['coin_symbol'] = $request->post('coin_symbol');
        CreateWallet::create_v2($request->post('user_id'));
    }

    // 启用禁用用户
    public function actionChangeStatus()
    {
        // 获取参数
        $request = Yii::$app->request;
        $uid     = $request->get('uid')??'';
        $status  = $request->get('status')??'';
        if ($uid=='' || $status=='') {
            return $this->message("参数不完整！",$this->redirect(['index']),'error');
        }

        // 更新status字段
        $member = Member::find()
            ->where(['id'=>intval($uid)])
            ->andWhere(['status'=>intval($status)])
            ->one();
        if (empty($member)) {
            return $this->message("查询不到该用户信息！",$this->redirect(['index']),'error');
        }
        $member->status = intval($status) == 0 ? 10 : 0;
        if ($member->save()) {
            return $this->message("修改成功！",$this->redirect(['index']),'success');
        }else{
            return $this->message("修改失败！",$this->redirect(['index']),'error');
        }
    }

    public function actionUserFriend(){
        $request  = Yii::$app->request;
        $id       = $request->get('id');

        
        // 1用户基本信息
        $data = Member::find();
        // $data   = Member::find()->with(['verified']);

        $where[]='and';
        if (!empty($starttime)) {
            $where[]=array('>','jl_member.created_at',$starttime);
        }
        if (!empty($endtime)) {
            $where[]=array('<','jl_member.created_at',$endtime);
        }
        $where[]=array('=','jl_member.last_member',$id);
        $data->where($where);
        $count = $data->count();
        $pagination = new Pagination(['totalCount' => $count]);
        $models = $data->offset($pagination->offset)
           ->orderBy('type desc,created_at desc')
           ->limit($pagination->limit)
           ->all();
        return $this->render('user-friend',[
           'models'  => $models,
           'pagination'   => $pagination,
           'status'  => $this->VERIFIED_STATUS,
           'status_color' => $this->STATUS_COLOR,
        ]);
    }

    // 更新汇率字段
    public function actionChangeRate()
    {
        $request = Yii::$app->request;
        $id      = $request->post('id');
        $rate    = $request->post('rate');
        $type    = $request->post('type');

        if (empty($rate)) {
            $rate = 0;
        }

        $result = [
            'code'    => 500,
            'message' => '',
        ];

        $member = Member::find()->where(['id' => $id])->one();
        if ($type == 'proportion') {
            $member->proportion = floatval($rate);
        }else{
            $member->proportion = floatval($rate);
        }
        if ($member->save()) {
            $result['code'] = 200;
        }else{
            $result['message'] = $member->getErrors();
        }

        return json_encode($result);
    }

    // 推荐架构
    public function actionRecommend()
    {
        $request = Yii::$app->request;

        // 输入用户信息，查找该用户的下级树
        $pid      = $request->get('keyword');

        $user  =   Member::find();
        if (empty($pid)) {
            $pid = 1000463;
        }
        $k_where['id'] = $pid;
        $query=$user->where($k_where)->one();
        if (empty($query)){
            return $this->render('recommend',[
               'tree'  => '查询的用户不存在',
               'keyword'  => $pid,
            ]);
        }
        if($pid!='0')
        {
            $k_where['id'] = $pid;
            $k_where2['mobile_phone'] = $pid;
            $query=$user->where($k_where)->one()->toArray();

            $pid=$query['id'];
            $pid2=$query['last_member'];
        }
        $tree     =   $this->getTree($pid2,$pid,1);
        return $this->render('recommend',[
           'tree'  => $tree,
           'keyword'  => $pid,
        ]);
    }

    // 推荐架构
    public function actionMyrecommend()
    {
        // 输入用户信息，查找该用户的下级树
        $request = Yii::$app->request;
        $id      = $request->get('id');
        $user  =   Member::find();
        if (empty($pid)) {
            $pid = 1000463;
        }

        if($pid!='0')
        {
            $k_where['id'] = $pid;
            $k_where2['mobile_phone'] = $pid;
            $query=$user->where($k_where)->one()->toArray();
            $pid=$query['id'];
            $pid2=$query['last_member'];
        }
        $tree     =   $this->getTree($pid2,$pid,1);
        return $this->render('myrecommend',[
           'tree'  => $tree,
           'keyword'  => $pid,
        ]);
    }


    public  function getTree($pid='0',$uid=0,$ceng=0)
    {
        $t=Member::find();
        if ($ceng == 1) {
            $wherea['id']=$uid;
        }
        $wherea['last_member']=$pid;
        //$list=$t->where(array('pid'=>$pid,'sex'==0))->order('userid asc')->select();

        $list=$t->where($wherea)->orderBy('id asc')->asArray()->all();

        if(is_array($list)){
                $html = '';
                foreach($list as $k => $v)
                {
                    $map['last_member']=$v['id'];
                    $count=$t->where($map)->count(1);
                    $class=$count==0 ? 'fa-user':'fa-sitemap';
                   if($v['last_member'] == $pid)
                   {  
                        if (empty($v['mobile_phone'])) {
                            $v['mobile_phone'] = '手机号未绑定';
                        }
                        if (empty($v['email'])) {
                            $v['email'] = '邮箱未绑定';
                        }
                        //父亲找到儿子了
                        $html .= '<li style="display:none" >';
                        $html .= '<span><i class="icon-sitemap '.$class.' blue "></i>';
                        $html .= $v['mobile_phone'];
                        $html .= '</span>';
                        $html .= $v['email'];
                        $html .= ' <a href="'.Url::to(['aguser-detail','id'=>$v['id']]).'" class="blue">查看持仓及交易记录</a>';
                        $html .= $this->getTree($v['id']);
                        $html = $html."</li>";
                   }
                }
            return $html ? '<ul>'.$html.'</ul>' : $html ;
        }
    }


    public function share(){
        // 输入用户信息，查找该用户的下级树
        $pid   =   input('keyword', '0', 'string');
        $user  =   Db::name('user');
        if (empty($pid)) {
            $pid = 8000142;
        }
        $this->assign('keyword',$pid);
        if($pid!='0')
        {
            $k_where['id|mobile'] = $pid;
            $query=$user->where($k_where)->find();
            $pid=$query['id'];
            $pid2=$query['last_member'];

            //推荐人数
            $zhitui_count = $user->where(['last_member' => $pid])->count();
            $all_count = $user->where('parent_tree','like','%'.$pid.'%')->count();

            //推荐业绩
            $zhitui_user = $user->where(['last_member' => $pid])->field('id')->select()->toarray();
            $zhitui_num = 0;
            foreach($zhitui_user as $k => $v){
                $Temp_yeji = Db::name('wealth_order')->where(['user_id' => $v['id']])->sum('amount');
                $zhitui_num = $zhitui_num + $Temp_yeji;
            }
            $all_user = $user->where('parent_tree','like','%'.$pid.'%')->field('id')->select()->toarray();
            $all_num = 0;
            foreach($all_user as $k => $v){
                $Temp_yeji = Db::name('wealth_order')->where(['user_id' => $v['id']])->sum('amount');
                $all_num = $all_num + $Temp_yeji;
            }
            
            $this->assign('zhitui_count',$zhitui_count);
            $this->assign('all_count',$all_count);
            $this->assign('zhitui_num',$zhitui_num);
            $this->assign('all_num',$all_num);
        }
        $tree     =   $this->getTree($pid2,$pid,1);
        $this->assign('tree',$tree);
        return $this->fetch();
    }

















}