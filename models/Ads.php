<?php

namespace app\models;

use Yii;
use yii\web\UploadedFile;

/**
 * This is the model class for table "ads".
 *
 * @property integer $id
 * @property string $uuid
 * @property string $name
 * @property integer $position
 * @property string $url
 */
class Ads extends \yii\db\ActiveRecord
{
    const POSITION_LEFT_1 = 0;
    const POSITION_LEFT_2 = 1;
    const POSITION_LEFT_3 = 2;
    const POSITION_LEFT_4 = 3;
    const POSITION_RIGHT_1 = 10;
    const POSITION_RIGHT_2 = 11;
    const POSITION_RIGHT_3 = 12;

    public static $positions = [
        self::POSITION_LEFT_1 => 'Слева, №1, 180x380',
        self::POSITION_LEFT_2 => 'Слева, №2, 180x180',
        self::POSITION_LEFT_3 => 'Слева, №3, 180x280',
        self::POSITION_LEFT_4 => 'Слева, №4, 180x280',
        self::POSITION_RIGHT_1 => 'Справа, №1, 195x240',
        self::POSITION_RIGHT_2 => 'Справа, №2, 195x410',
        self::POSITION_RIGHT_3 => 'Справа, №3, 195x170',
    ];

    /**
     * @var UploadedFile
     */
    public $imageFile;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ads';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'position', 'imageFile'], 'required'],
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png, jpg', 'on' => 'create'],
            [['position'], 'integer'],
            [['uuid'], 'string', 'max' => 64],
            [['name', 'url'], 'string', 'max' => 255],
            [['url'], 'url'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uuid' => 'UUID',
            'imageFile' => 'Баннер',
            'name' => 'Название',
            'position' => 'Позиция',
            'url' => 'URL',
        ];
    }

    /**
     * Генерация нового UUID.
     *
     * @return string
     */
    private function generateUUID()
    {
        return uniqid("", true);
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate() {
        $this->imageFile = UploadedFile::getInstance($this, 'imageFile');

        return parent::beforeValidate();
    }

    /**
     * Получение полного пути к файлу.
     *
     * @return mixed
     */
    public function getPath()
    {
        $files = glob(Yii::getAlias('@app/data/ads') . '/' . $this->uuid . '.*');
        return reset($files);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($this->isNewRecord)
            $this->uuid = $this->generateUUID();

        if (!is_null($this->imageFile))
            $this->imageFile->saveAs(Yii::getAlias('@app/data/ads') . '/' . $this->uuid . '.' . $this->imageFile->extension);

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        Yii::$app->cache->delete('ads');
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        parent::afterDelete();

        unlink($this->getPath());

        Yii::$app->cache->delete('ads');
    }
}
