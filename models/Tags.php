<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tags".
 *
 * @property integer $id
 * @property integer $video_id
 * @property integer $type
 * @property string $text
 */
class Tags extends \yii\db\ActiveRecord
{
    const TYPE_TAG = 0;
    const TYPE_CHANNEL = 1;
    const TYPE_TITLE = 2;

    /**
     * @var array Веса тэгов.
     */
    public static $weights = [
        self::TYPE_TAG => 1,
        self::TYPE_CHANNEL => 2,
        self::TYPE_TITLE => 10,
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['video_id'], 'required'],
            [['video_id', 'type'], 'integer'],
            [['text'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'video_id' => 'Видео',
            'type' => 'Тип',
            'text' => 'Text',
        ];
    }

    /**
     * Получение веса тэга.
     *
     * @return mixed
     */
    public function getWeight()
    {
        return self::$weights[ $this->type ];
    }
}
