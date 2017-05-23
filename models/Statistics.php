<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistics".
 *
 * @property integer $id
 * @property string $timestamp
 * @property integer $views
 * @property integer $likes
 * @property integer $dislikes
 * @property integer $video_id
 *
 * @property Videos $video
 */
class Statistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'statistics';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['timestamp', 'video_id'], 'required'],
            [['timestamp'], 'safe'],
            [['views', 'likes', 'dislikes', 'video_id'], 'integer'],
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
            'timestamp' => 'Timestamp',
            'views' => 'Views',
            'likes' => 'Likes',
            'dislikes' => 'Dislikes',
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
     * @inheritdoc
     * @return StatisticsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new StatisticsQuery(get_called_class());
    }
}
