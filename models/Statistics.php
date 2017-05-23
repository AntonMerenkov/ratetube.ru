<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "statistics".
 *
 * @property integer $id
 * @property string $datetime
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
     * Типы запросов по времени.
     */
    const QUERY_TIME_MINUTE = 'minute';
    const QUERY_TIME_HOUR = 'hour';
    const QUERY_TIME_DAY = 'day';
    const QUERY_TIME_WEEK = 'week';
    const QUERY_TIME_MONTH = 'month';

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
            [['datetime', 'video_id'], 'required'],
            [['datetime'], 'safe'],
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
            'datetime' => 'Datetime',
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

    /**
     * Получение статистики по видео при помощи Youtube API.
     *
     * @param $videoIds
     * @return array
     */
    public static function getByVideoIds($videoIds)
    {
        $result = [];
        $urlArray = [];

        foreach (array_chunk($videoIds, 50) as $videoIdsChunk)
            $urlArray[] = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query(array(
                    'part' => 'statistics',
                    'maxResults' => 50,
                    'id' => implode(',', $videoIdsChunk),
                    'key' => Yii::$app->params[ 'apiKey' ]
                ));

        $responseArray = Yii::$app->curl->queryMultiple($urlArray);

        foreach ($responseArray as $response) {
            $response = json_decode($response, true);

            if (!empty($response[ 'items' ]))
                foreach ($response[ 'items' ] as $item)
                    $result[ $item[ 'id' ] ] = $item[ 'statistics' ];
        }

        return $result;
    }
}
