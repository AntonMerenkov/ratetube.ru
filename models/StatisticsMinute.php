<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistics_minute".
 *
 * @property integer $id
 * @property string $datetime
 * @property integer $views
 * @property integer $likes
 * @property integer $dislikes
 * @property integer $video_id
 * @property integer $viewers
 *
 * @property Videos $video
 */
class StatisticsMinute extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'statistics_minute';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['datetime', 'video_id'], 'required'],
            [['datetime'], 'safe'],
            [['views', 'likes', 'dislikes', 'viewers', 'video_id'], 'integer'],
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
            'datetime' => 'Datetime',
            'views' => 'Views',
            'likes' => 'Likes',
            'dislikes' => 'Dislikes',
            'viewers' => 'Viewers',
            'video_id' => 'Video ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVideo()
    {
        return $this->hasOne(Videos::className(), ['id' => 'video_id']);
    }

    /**
     * Форматирование даты.
     *
     * @return bool
     */
    public function beforeValidate()
    {
        $this->datetime = date('Y-m-d H:i:s', strtotime($this->datetime));

        return parent::beforeValidate();
    }
}
