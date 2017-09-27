<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "positions".
 *
 * @property integer $id
 * @property integer $video_id
 * @property integer $position
 *
 * @property PositionStatistics[] $positionStatistics
 * @property Videos $video
 */
class Positions extends \yii\db\ActiveRecord
{
    /**
     * @var string Переменные для добавления нового видео.
     */
    public $url;
    public $videoLink;
    public $imageUrl;
    public $videoName;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'positions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['videoName', 'imageUrl'], 'string', 'max' => 255],
            [['videoLink'], 'string', 'max' => 32],
            [['video_id', 'position'], 'required'],
            [['video_id', 'position'], 'integer'],
            [['video_id'], 'exist', 'skipOnError' => true, 'targetClass' => Videos::className(), 'targetAttribute' => ['video_id' => 'id']],
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
            'position' => 'Позиция',
            'url' => 'Ссылка на видео',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPositionStatistics()
    {
        return $this->hasMany(PositionStatistics::className(), ['position_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVideo()
    {
        return $this->hasOne(Videos::className(), ['id' => 'video_id']);
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if ($this->videoLink) {
            // находим, есть ли такое видео в таблице
            $videoModel = Videos::find()->where(['video_link' => $this->videoLink])->one();

            if (!is_null($videoModel)) {
                if (!$videoModel->active) {
                    $videoModel->active = 1;
                    $videoModel->save();
                }
            } else {
                $videoModel = new Videos();
                $videoModel->name = $this->videoName;
                $videoModel->video_link = $this->videoLink;
                $videoModel->image_url = $this->imageUrl;
                $videoModel->save();
            }

            $this->video_id = $videoModel->id;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        parent::afterFind();

        $this->url = 'https://www.youtube.com/watch?v=' . $this->video->video_link;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->position = 1;
    }
}
