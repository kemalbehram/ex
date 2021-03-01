<?php
namespace jianyan\basics\backend\modules\wechat\controllers;

use Yii;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Response;
use EasyWeChat\Kernel\Messages\Article;
use jianyan\basics\common\models\wechat\Attachment;
use jianyan\basics\common\models\wechat\News;
use jianyan\basics\backend\modules\wechat\models\NewsPreview;

/**
 * 素材控制器
 *
 * Class AttachmentController
 * @package jianyan\basics\backend\modules\wechat\controllers
 */
class AttachmentController extends WController
{
    /**
     * 微信素材域名
     *
     * @var string
     */
    protected $_wechaMediatUrl = 'http://mmbiz.qpic.cn';

    /**
     * 因为微信图片做了防盗链,重新获取微信图片转接地址
     *
     * @var
     */
    protected $_getWecahtMediatUrl;

    /**
     * 发送预览群发消息给指定的 openId 用户
     *
     * @var array
     */
    protected $_preview = [
        'text' => 'previewText',
        'news' => 'previewNews',
        'voice' => 'previewVoice',
        'image' => 'previewImage',
        'video' => 'previewVideo',
        'card' => 'previewCard',
    ];

    /**
     * 发送预览群发消息给指定的微信号用户
     *
     * @var array
     */
    protected $_previewByName = [
        'text' => 'previewTextByName',
        'news' => 'previewNewsByName',
        'voice' => 'previewVoiceByName',
        'image' => 'previewImageByName',
        'video' => 'previewVideoByName',
        'card' => 'previewCardByName',
    ];

    /**
     * 图文首页
     *
     * @return string
     */
    public function actionNewsIndex()
    {
        $mediaType = Attachment::TYPE_NEWS;
        $attachment = Attachment::getList($mediaType);

        return $this->render('news-index',[
            'wechatMediaType'   => Yii::$app->params['wechatMediaType'],
            'mediaType'   => $mediaType,
            'attachment' => $attachment
        ]);
    }

    /**
     * 图文编辑
     *
     * @return string|array
     */
    public function actionNewsEdit()
    {
        $request  = Yii::$app->request;
        // 获取图片的链接地址
        $this->_getWecahtMediatUrl = Url::to(['we-code/image']) . "?attach=";

        $attach_id  = $request->get('attach_id','');
        $attachment = $this->findModel($attach_id);

        if ($request->isAjax)
        {
            Yii::$app->response->format = Response::FORMAT_JSON;
            // 素材库
            $material = $this->_app->material;
            // 本地图片前缀
            $prefix =  Yii::getAlias("@rootPath/").'web';

            $attach_id = $request->post('attach_id');
            $attachment = $this->findModel($attach_id);

            $list = $request->post('list');
            $list = json_decode($list,true);

            // 图文详情
            $article_list = [];
            foreach ($list as $key => &$item)
            {
                // 替换加入显示的数据
                $item['content'] = str_replace($this->_getWecahtMediatUrl,'',trim($item['content']));
                $item['thumb_url'] = str_replace($this->_getWecahtMediatUrl,'',trim($item['thumb_url']));
                // 原始封面
                $thumb_url = $item['thumb_url'];

                // 封面判断是否已经上传到微信了
                if(strpos(urldecode($item['thumb_url']),$this->_wechaMediatUrl) === false)
                {
                    // 上传到微信
                    $image_material = $material->uploadImage($prefix . $thumb_url);
                    $item['thumb_media_id'] = $image_material['media_id'];
                    $item['thumb_url'] = $image_material['url'];

                    Attachment::addImage($image_material,$prefix . $thumb_url);
                }

                // 循环上传文章图片到微信
                preg_match_all('/<img[^>]*src\s*=\s*([\'"]?)([^\'" >]*)\1/isu', $item['content'],$match);
                foreach ($match[2] as $src)
                {
                    // 判断是否已经上传到微信了
                    if(strpos(urldecode($src),$this->_wechaMediatUrl) === false)
                    {
                        $result = $material->uploadArticleImage($prefix . $src);
                        $url = $result['url'];
                        // 替换图片上传
                        $item['content'] = str_replace($src, $url, $item['content']);
                    }
                }

                $item['content'] = htmlspecialchars_decode($item['content']);

                // 默认微信返回值
                $article = new Article([
                    'title' => $item['title'],
                    'thumb_media_id' => $item['thumb_media_id'],
                    'author' => $item['author'],
                    'content' => $item['content'],
                    'digest' => $item['digest'],
                    'source_url' => $item['content_source_url'],
                    'show_cover' => $item['show_cover_pic'],
                ]);

                $article_list[] = $article;
            }

            // 编辑
            if($attach_id)
            {
                // 更新到微信
                $material->updateArticle($attachment['media_id'], $article_list);

                // 插入文章到表
                foreach ($list as $k => $vo)
                {
                    $news = News::findOne($vo['id']);
                    $news->sort = $k;
                    $news->attributes = $vo;
                    $news->save();
                }
            }
            else
            {
                // 上传图文信息
                $resource = $material->uploadArticle($article_list);
                // 获取图文信息
                $getNews = $material->get($resource['media_id']);
                $news_item = $getNews['news_item'];

                // 图文素材创建
                $attachment->media_id = $resource['media_id'];
                $attachment->manager_id = Yii::$app->user->id;
                $attachment->type = Attachment::TYPE_NEWS;
                $attachment->save();

                // 插入文章到表
                foreach ($list as $k => $vo)
                {
                    $vo['attach_id'] = $attachment->id;
                    $news = new News();
                    $news->attributes = $vo;
                    $news->url = $news_item[$k]['url'];
                    $news->sort = $k;
                    $news->save();
                }
            }

            $result = $this->setResult();
            $result->code = 200;
            $result->message = '成功';
            return $this->getResult();
        }

        return $this->render('news-edit',[
            'attachment' => $attachment,
            'list' => json_encode(News::getList($attach_id)),
            'attach_id' => $attach_id
        ]);
    }

    /**
     * 图文链接编辑
     *
     * @return string|array
     */
    public function actionNewsLinkEdit()
    {
        $request  = Yii::$app->request;
        // 获取图片的链接地址
        $this->_getWecahtMediatUrl = Url::to(['we-code/image']) . "?attach=";

        $attach_id  = $request->get('attach_id','');
        $attachment = $this->findModel($attach_id);

        if ($request->isAjax)
        {
            // 素材库
            $material = $this->_app->material;
            // 本地图片前缀
            $prefix =  Yii::getAlias("@rootPath/") . 'web';

            $attach_id = $request->post('attach_id');
            $attachment = $this->findModel($attach_id);

            $list = $request->post('list');
            $list = json_decode($list,true);

            // 图文详情
            foreach ($list as $key => &$item)
            {
                // 替换加入显示的数据
                $item['thumb_url'] = str_replace($this->_getWecahtMediatUrl, '', trim($item['thumb_url']));
                // 原始封面
                $thumb_url = $item['thumb_url'];

                // 封面判断是否已经上传到微信了
                if(strpos(urldecode($item['thumb_url']), $this->_wechaMediatUrl) === false)
                {
                    // 上传到微信
                    $image_material = $material->uploadImage($prefix . $thumb_url);
                    $item['thumb_media_id'] = $image_material['media_id'];
                    $item['thumb_url'] = $image_material['url'];

                    Attachment::addImage($image_material,$prefix . $thumb_url);
                }
            }

            // 编辑
            if($attach_id)
            {
                // 插入文章到表
                foreach ($list as $k => $vo)
                {
                    $news = News::findOne($vo['id']);
                    $news->sort = $k;
                    $news->attributes = $vo;
                    $news->url = $vo['source_url'];
                    $news->save();
                }
            }
            else
            {
                // 图文素材创建
                $attachment->link_type = Attachment::LINK_TYPE_LOCAL;
                $attachment->manager_id = Yii::$app->user->id;
                $attachment->type = Attachment::TYPE_NEWS;
                $attachment->save();

                // 插入文章到表
                foreach ($list as $k => $vo)
                {
                    $vo['attach_id'] = $attachment->id;
                    $news = new News();
                    $news->attributes = $vo;
                    $news->url = $vo['source_url'];
                    $news->sort = $k;
                    $news->save();
                }
            }

            $result = $this->setResult();
            $result->code = 200;
            $result->message = '成功';
            return $this->getResult();
        }

        return $this->render('news-link-edit',[
            'attachment' => $attachment,
            'list' => json_encode(News::getList($attach_id)),
            'attach_id' => $attach_id
        ]);
    }

    /**
     * 手机预览
     *
     * @param $attach_id
     * @return mixed|string
     */
    public function actionNewsPreview($attach_id)
    {
        $attachment = Attachment::getOne($attach_id);
        $model = new NewsPreview();
        $model->msg_type = $attachment->type;
        $model->media_id = $attachment->media_id;
        $model->type = 1;

        if ($model->load(Yii::$app->request->post()))
        {
            try
            {
                $broadcast = $this->_app->broadcasting;
                if($model->type == 1)
                {
                    // 微信号预览
                    $method = $this->_previewByName[$model->msg_type];
                    $broadcast->$method($model->media_id, $model->content);
                }
                else
                {
                    // openid预览
                    $method = $this->_preview[$model->msg_type];
                    $broadcast->$method($model->media_id, $model->content);
                }
            }
            catch (\Exception $e)
            {
                return $this->message($e->getMessage(), $this->redirect(['attachment/' . $model['msg_type'] . '-index']), 'error');
            }

            return $this->message("发送成功", $this->redirect(['attachment/'.$model['msg_type'] . '-index']));
        }

        return $this->renderAjax('news-preview',[
            'model' => $model,
        ]);
    }

    /**
     * 图片首页
     *
     * @return string
     */
    public function actionImageIndex()
    {
        $mediaType = Attachment::TYPE_IMAGE;
        $data = Attachment::find()->where(['type'=>$mediaType]);
        $pages = new Pagination(['totalCount' =>$data->count(), 'pageSize' => 15]);
        $models = $data->offset($pages->offset)
            ->orderBy('append desc')
            ->limit($pages->limit)
            ->all();

        return $this->render('image-index',[
            'models' => $models,
            'pages' => $pages,
            'wechatMediaType' => Yii::$app->params['wechatMediaType'],
            'mediaType' => $mediaType,
        ]);
    }

    /**
     * 图片添加
     * @return string|Response
     */
    public function actionImageAdd()
    {
        $model = $this->findModel('');
        if ($model->load(Yii::$app->request->post()))
        {
            if($model->attachment)
            {
                // 本地图片前缀
                $prefix =  Yii::getAlias("@rootPath/") . 'web';
                $material = $this->_app->material;
                $image_material = $material->uploadImage($prefix . $model->attachment);

                Attachment::addImage($image_material, $prefix . $model->attachment);

                return $this->redirect(['image-index']);
            }
        }

        return $this->renderAjax('image-add',[
            'model' => $model
        ]);
    }

    /**
     * 视频首页
     *
     * @return string
     */
    public function actionVideoIndex()
    {
        $mediaType = Attachment::TYPE_VIDEO;
        $data = Attachment::find()->where(['type' => $mediaType]);
        $pages = new Pagination(['totalCount' =>$data->count(), 'pageSize' => 15]);
        $models = $data->offset($pages->offset)
            ->orderBy('append desc')
            ->limit($pages->limit)
            ->all();

        return $this->render('video-index',[
            'models'  => $models,
            'pages'   => $pages,
            'wechatMediaType'   => Yii::$app->params['wechatMediaType'],
            'mediaType'   => $mediaType,
        ]);
    }

    /**
     * 视频添加
     *
     * @return string|Response
     */
    public function actionVideoAdd()
    {
        $model = $this->findModel('');

        if (Yii::$app->request->isPost)
        {
            $data = Yii::$app->request->post('Attachment');
            if($data['attachment'])
            {
                // 本地图片前缀
                $prefix =  Yii::getAlias("@rootPath/") . 'web';
                $material = $this->_app->material;

                $res_material = $material->uploadVideo($prefix . $data['attachment'], $data['file_name'], $data['description']);

                Attachment::addVideo($res_material, $prefix . $data['attachment'], $data['file_name']);
            }

            return $this->redirect(['video-index']);
        }

        return $this->renderAjax('video-add',[
            'model' => $model
        ]);
    }

    /**
     * 声音首页
     *
     * @return string
     */
    public function actionVoiceIndex()
    {
        $mediaType = Attachment::TYPE_VOICE;

        $data = Attachment::find()->where(['type' => $mediaType]);
        $pages = new Pagination(['totalCount' => $data->count(), 'pageSize' => 15]);
        $models = $data->offset($pages->offset)
            ->orderBy('append desc')
            ->limit($pages->limit)
            ->all();

        return $this->render('voice-index',[
            'models' => $models,
            'pages' => $pages,
            'wechatMediaType' => Yii::$app->params['wechatMediaType'],
            'mediaType' => $mediaType,
        ]);
    }

    /**
     * 声音添加
     *
     * @return string|Response
     */
    public function actionVoiceAdd()
    {
        $model = $this->findModel('');
        if ($model->load(Yii::$app->request->post()))
        {
            if($model->attachment)
            {
                // 本地图片前缀
                $prefix =  Yii::getAlias("@rootPath/").'web';
                $material = $this->_app->material;
                $res_material = $material->uploadVoice($prefix . $model->attachment);
                // 插入数据库
                Attachment::addVoice($res_material, $prefix . $model->attachment);

                return $this->redirect(['voice-index']);
            }
        }

        return $this->renderAjax('voice-add',[
            'model' => $model
        ]);
    }

    /**
     * 删除永久素材
     */
    public function actionDelete($attach_id)
    {
        // 删除数据库
        $attachment = $this->findModel($attach_id);
        $attachment->delete();
        // 删除微信服务器数据
        $material = $this->_app->material;
        $material->delete($attachment['media_id']);

        return $this->message("删除成功",$this->redirect([$attachment['type'].'-index']));
    }

    /**
     * 同步微信素材
     *
     * @param string $type 素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）
     * @param int $offset 从全部素材的该偏移位置开始返回，可选，默认 0，0 表示从第一个素材 返回
     * @param int $count 返回素材的数量，可选，默认 20, 取值在 1 到 20 之间
     * @return mixed
     */
    public function actionGetAllAttachment($type, $offset = 0, $count = 20)
    {
        $result = $this->setResult();

        // 查找素材
        $material = $this->_app->material;
        $lists = $material->list($type, $offset, $count);
        $total = $lists['total_count'];

        // 素材列表
        $list = $lists['item'];

        $add_material = [];
        $sys_material = [];
        foreach ($list as $vo)
        {
            $sys_material[] = $vo['media_id'];
        }

        // 系统内的素材
        $system_material = Attachment::find()
            ->where(['in','media_id',$sys_material])
            ->select('media_id')
            ->asArray()
            ->all();

        $new_system_material = [];
        foreach ($system_material as $li)
        {
            $new_system_material[$li['media_id']] = $li;
        }

        switch ($type)
        {
            // ** 图文 **// 
            case Attachment::TYPE_NEWS :

                foreach ($list as $vo)
                {
                    if (empty($new_system_material) || empty($new_system_material[$vo['media_id']]))
                    {
                        $attachment = new Attachment();
                        $attachment->manager_id = Yii::$app->user->id;
                        $attachment->media_id = $vo['media_id'];
                        $attachment->model = Attachment::MODEL_PERM;
                        $attachment->type = $type;
                        $attachment->append = $vo['update_time'];
                        $attachment->save();

                        $attach_id = $attachment->id;
                        // 插入文章
                        foreach ($vo['content']['news_item'] as $key => $content)
                        {
                            $news = new News();
                            $news->attributes = $content;
                            $news->content = str_replace("data-src","src",$news->content);
                            $news->attach_id = $attach_id;
                            $news->sort = $key;
                            $news->save();
                        }
                    }
                }

                break;

            //** 图片 **//
            case Attachment::TYPE_IMAGE :

                foreach ($list as $vo)
                {
                    if (empty($new_system_material) || empty($new_system_material[$vo['media_id']]))
                    {
                        $add_material[] = [Yii::$app->user->id, Attachment::MODEL_PERM, $vo['name'], $vo['media_id'], $type, $vo['url'], $vo['update_time'], time()];
                    }
                }

                if (!empty($add_material))
                {
                    // 批量插入数据
                    $field = ['manager_id','model','file_name','media_id','type','tag','append','updated'];
                    Yii::$app->db->createCommand()->batchInsert(Attachment::tableName(),$field, $add_material)->execute();
                }

                break;

            //** 视频 **//
            case Attachment::TYPE_VIDEO :

                foreach ($list as $vo)
                {
                    if (empty($new_system_material) || empty($new_system_material[$vo['media_id']]))
                    {
                        $add_material[] = [Yii::$app->user->id, Attachment::MODEL_PERM, $vo['name'], $vo['media_id'], $type, $vo['update_time'], time()];
                    }
                }

                if (!empty($add_material))
                {
                    // 批量插入数据
                    $field = ['manager_id','model','file_name','media_id','type','append','updated'];
                    Yii::$app->db->createCommand()->batchInsert(Attachment::tableName(),$field, $add_material)->execute();
                }

                break;
            //** 语音 **//
            case Attachment::TYPE_VOICE :

                foreach ($list as $vo)
                {
                    if (empty($new_system_material) || empty($new_system_material[$vo['media_id']]))
                    {
                        $add_material[] = [Yii::$app->user->id, Attachment::MODEL_PERM, $vo['name'], $vo['media_id'], $type, $vo['update_time'], time()];
                    }
                }

                if (!empty($add_material))
                {
                    // 批量插入数据
                    $field = ['manager_id','model','file_name','media_id','type','append','updated'];
                    Yii::$app->db->createCommand()->batchInsert(Attachment::tableName(),$field, $add_material)->execute();
                }

                break;
        }

        $result->message = "同步完成!";
        if($total - $count > 0)
        {
            $result->code = 200;
            $result->message = "同步成功!";
            $result->data = [
                'offset' => ($offset + 1) * $count,
                'count' => $count + $count
            ];
        }

        return $this->getResult();
    }

    /**
     * 返回模型
     * @param $id
     * @return $this|Attachment|static
     */
    protected function findModel($id)
    {
        if (empty($id))
        {
            $model = new Attachment;
            return $model->loadDefaultValues();
        }

        if (empty(($model = Attachment::findOne($id))))
        {
            return new Attachment;
        }

        return $model;
    }
}