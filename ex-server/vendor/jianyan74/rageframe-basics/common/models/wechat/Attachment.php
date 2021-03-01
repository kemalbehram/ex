<?php

namespace jianyan\basics\common\models\wechat;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%wechat_attachment}}".
 *
 * @property string $id
 * @property string $manager_id
 * @property string $filename
 * @property string $attachment
 * @property string $media_id
 * @property string $width
 * @property string $height
 * @property string $type
 * @property string $model
 * @property string $tag
 * @property string $append
 */
class Attachment extends \yii\db\ActiveRecord
{
    const TYPE_NEWS = 'news';
    const TYPE_TEXT = 'text';
    const TYPE_VOICE = 'voice';
    const TYPE_IMAGE = 'image';
    const TYPE_CARD = 'card';
    const TYPE_VIDEO = 'video';

    public $typeExplain = [
        self::TYPE_NEWS => '图文素材',
        self::TYPE_TEXT => '文字素材',
        self::TYPE_VOICE => '音频素材',
        self::TYPE_IMAGE => '图片素材',
        self::TYPE_CARD => '卡卷素材',
        self::TYPE_VIDEO => '视频素材',
    ];

    const MODEL_PERM = 'perm';
    const MODEL_TMEP = 'tmep';

    public $modeExplain = [
        self::MODEL_PERM => '永久素材',
        self::MODEL_TMEP => '临时素材',
    ];

    const LINK_TYPE_WECHAT = 1;
    const LINK_TYPE_LOCAL = 2;

    public $linkTypeExplain = [
        self::LINK_TYPE_WECHAT => '微信图文',
        self::LINK_TYPE_LOCAL => '本地图文',
    ];

    public $description;

    /**
     * 微信上传配置
     *
     * @var array
     */
    protected $limit = [
        // 临时素材
        'temp' => [
            'image' => [
                'ext'  => ['jpg', 'logo'],
                'size' => 1048576,// 1024 * 1024
                'errmsg' => '临时图片只支持jpg/logo格式,大小不超过为1M',
            ],
            'voice' => [
                'ext'  => ['amr', 'mp3'],
                'size' => 2097152,// 2048 * 1024
                'errmsg' => '临时语音只支持amr/mp3格式,大小不超过为2M',
            ],
            'video' => [
                'ext'  => ['mp4'],
                'size' => 10485760,// 10240 * 1024
                'errmsg' => '临时视频只支持mp4格式,大小不超过为10M',
            ],
            'thumb' => [
                'ext'  => ['jpg', 'logo'],
                'size' => 65536,// 64 * 1024
                'errmsg' => '临时缩略图只支持jpg/logo格式,大小不超过为64K',
            ],
        ],
        // 永久素材
        'perm' => [
            'image' => [
                'ext'   => ['bmp', 'png', 'jpeg', 'jpg', 'gif'],
                'size'  => 2097152,// 2048 * 1024
                'max'   => 5000,
                'errmsg' => '永久图片只支持bmp/png/jpeg/jpg/gif格式,大小不超过为2M',
            ],
            'voice' => [
                'ext'  => ['amr', 'mp3', 'wma', 'wav', 'amr'],
                'size' => 5242880,// 5120 * 1024
                'max'  => 1000,
                'errmsg' => '永久语音只支持mp3/wma/wav/amr格式,大小不超过为5M,长度不超过60秒',
            ],
            'video' => [
                'ext'  => ['rm', 'rmvb', 'wmv', 'avi', 'mpg', 'mpeg', 'mp4'],
                'size' => 20971520,// 10240 * 1024 * 2
                'max'  => 1000,
                'errmsg' => '永久视频只支持rm/rmvb/wmv/avi/mpg/mpeg/mp4格式,大小不超过为20M',
            ],
            'thumb' => [
                'ext'  => ['bmp', 'png', 'jpeg', 'jpg', 'gif'],
                'size' => 2097152,// 2048 * 1024
                'max'  => 5000,
                'errmsg' => '永久缩略图只支持bmp/png/jpeg/jpg/gif格式,大小不超过为2M',
            ],
        ],
        // 卡卷封面
        'file_upload' => [
            'image' => [
                'ext'   => ['jpg'],
                'size'  => 1048576,// 1024 * 1024
                'max'   => -1,
                'errmsg' => '图片只支持jpg格式,大小不超过为1M',
            ],
        ]
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_attachment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['manager_id', 'type'], 'required'],
            [['manager_id', 'width', 'height', 'append','updated','link_type'], 'integer'],
            [['file_name', 'attachment', 'media_id'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 15],
            [['model'], 'string', 'max' => 10],
            [['tag'], 'string', 'max' => 5000],
            [['width','height'], 'safe'],
            ['model', 'default', 'value' => 'perm'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'manager_id' => '管理员id',
            'file_name' => '文件名称',
            'attachment' => '资源路径',
            'media_id' => '资源id',
            'width' => '宽度',
            'height' => '高度',
            'type' => '类别',
            'model' => '是否永久',
            'link_type' => '是否微信图文',
            'tag' => 'Tag',
            'description' => '视频说明',
            'append' => '创建时间',
            'updated' => '修改时间',
        ];
    }

    /**
     * 插入素材图片
     * @param $wxResult - 微信返回的信息
     * @param $imagePath - 本地的绝对路径
     */
    public static function addImage($wxResult, $imagePath)
    {
        $prefix =  Yii::getAlias("@rootPath/") . 'web';
        $img_info = getimagesize($imagePath);

        $image_model = new Attachment();
        $image_model->media_id = $wxResult['media_id'];
        $image_model->attachment = str_replace($prefix,'',trim($imagePath));;
        $image_model->type = self::TYPE_IMAGE;
        $image_model->width = $img_info[0];
        $image_model->height = $img_info[1];
        $image_model->file_name = array_slice(explode('/',$imagePath),-1,1)[0];
        $image_model->tag = $wxResult['url'];
        $image_model->manager_id = Yii::$app->user->id;

        return $image_model->save() ? true : false;
    }

    /**
     * @param $wxResult
     * @param $imagePath
     * @param $type
     */
    public static function addVoice($wxResult,$imagePath)
    {
        $prefix =  Yii::getAlias("@rootPath/").'web';

        $image_model = new Attachment();
        $image_model->media_id = $wxResult['media_id'];
        $image_model->attachment = str_replace($prefix,'',trim($imagePath));;
        $image_model->type =  self::TYPE_VOICE;
        $image_model->file_name = array_slice(explode('/',$imagePath),-1,1)[0];
        $image_model->manager_id = Yii::$app->user->id;

        return $image_model->save() ? true : false;
    }

    public static function addVideo($wxResult, $imagePath, $file_name)
    {
        $prefix =  Yii::getAlias("@rootPath/") . 'web';

        $image_model = new Attachment();
        $image_model->media_id = $wxResult['media_id'];
        $image_model->attachment = str_replace($prefix,'',trim($imagePath));;
        $image_model->type = self::TYPE_VIDEO;
        $image_model->file_name = $file_name;
        $image_model->manager_id = Yii::$app->user->id;

        return $image_model->save() ? true : false;
    }

    /**
     * 返回素材
     * @param $id
     * @return $this|Attachment|static
     */
    public static function getList($type)
    {
        return self::find()->where(['type' => $type])
            ->with('news')
            ->orderBy('append desc')
            ->asArray()
            ->all();
    }

    /**
     * 返回素材
     * @param $id
     * @return $this|Attachment|static
     */
    public static function getOne($id)
    {
        return self::findOne($id);
    }

    /**
     * 返回关联的图文信息
     * @return $this
     */
    public function getNews()
    {
        return $this->hasMany(News::className(),['attach_id'=>'id'])->orderBy('id asc');
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        // 删除文章详细详细
        if($this->type == self::TYPE_NEWS)
        {
            News::delAll($this->id);
        }

        return parent::beforeDelete();
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        $this->manager_id = Yii::$app->user->id;
        return parent::beforeSave($insert);
    }

    /**
     * 行为插入时间戳
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['append', 'updated'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated'],
                ],
            ],
        ];
    }
}
