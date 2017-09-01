<?php

namespace app\models;

use app\components\Statistics;
use Yii;
use yii\helpers\ArrayHelper;
use yii\sphinx\Query;

/**
 * This is the model class for table "videos".
 *
 * @property integer $id
 * @property string $name
 * @property string $video_link
 * @property integer $channel_id
 * @property integer $active
 *
 * @property Channels $channel
 */
class Videos extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'videos';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'channel_id'], 'required'],
            [['channel_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['video_link'], 'string', 'max' => 32],
            [['channel_id'], 'exist', 'skipOnError' => true, 'targetClass' => Channels::className(), 'targetAttribute' => ['channel_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Наименование',
            'video_link' => 'ID видео',
            'channel_id' => 'ID канала',
            'active' => 'Активен',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChannel()
    {
        return $this->hasOne(Channels::className(), ['id' => 'channel_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatistics()
    {
        $timeType = Yii::$app->session->get(Statistics::TIME_SESSION_KEY, Statistics::QUERY_TIME_HOUR);
        $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $timeType ];

        return $this->hasMany($tableModel::className(), ['video_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return VideosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new VideosQuery(get_called_class());
    }

    /**
     * Поиск видео на указанных каналах при помощи Youtube API.
     *
     * @param $channelIds
     * @return array
     */
    public static function getByChannelIds($channelIds)
    {
        $videoIds = [];

        $channelQueryData = array_map(function($item) {
            return [
                'id' => $item,
                'pageToken' => ''
            ];
        }, array_values($channelIds));

        do {
            $urlArray = array_map(function($item) {
                return 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
                    'part' => 'snippet',
                    'channelId' => $item[ 'id' ],
                    'maxResults' => 50,
                    'type' => 'video',
                    'order' => 'viewCount',
                    'key' => Yii::$app->params[ 'apiKey' ]
                ] + ($item[ 'pageToken' ] != '' ? ['pageToken' => $item[ 'pageToken' ]] : []));
            }, $channelQueryData);

            $responseArray = Yii::$app->curl->queryMultiple($urlArray);

            foreach ($responseArray as $id => $response) {
                $response = json_decode($response, true);

                if (!empty($response[ 'items' ]))
                    $videoIds = array_merge($videoIds, array_combine(array_map(function($item) {
                        return $item[ 'id' ][ 'videoId' ];
                    }, $response[ 'items' ]), array_map(function($item) use ($channelQueryData, $id) {
                        return [
                            'id' => $item[ 'id' ][ 'videoId' ],
                            'title' => $item[ 'snippet' ][ 'title' ],
                            'date' => date('Y-m-d H:i:s', strtotime($item[ 'snippet' ][ 'publishedAt' ])),
                            'channel_id' => $channelQueryData[ $id ][ 'id' ]
                        ];
                    }, $response[ 'items' ])));

                $channelQueryData[ $id ][ 'pageToken' ] = $response[ 'nextPageToken' ];
            }

            $channelQueryData = array_values(array_filter($channelQueryData, function($item) {
                return $item[ 'pageToken' ] != '';
            }));
        } while (!empty($channelQueryData));

        return $videoIds;
    }

    /**
     * Поиск по тэгам.
     * Возвращает ID найденных видео
     *
     * @param $query
     * @return array
     */
    public static function searchByQuery($query)
    {
        // поиск через Sphinx
        $sphinxQuery = new Query();
        $data = $sphinxQuery->from('tags')
            ->match($query)
            ->limit(1000)
            ->all();

        // сортировка с использованием весовых коэффициентов
        usort($data, function($a, $b) {
            return Tags::$weights[ $b[ 'type' ] ] - Tags::$weights[ $a[ 'type' ] ];
        });

        $data = array_map(function($item) {
            return $item[ 'video_id' ];
        }, $data);

        $data = array_values(array_unique($data));

        return $data;
    }
}
