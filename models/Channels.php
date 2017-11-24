<?php

namespace app\models;

use app\components\HighloadAPI;
use app\components\YoutubeAPI;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "channels".
 *
 * @property integer $id
 * @property string $name
 * @property string $url
 * @property string $channel_link
 * @property string $image_url
 * @property integer $category_id
 * @property string $flush_timeframe
 * @property integer $flush_count
 * @property integer $load_last_days
 * @property integer $subscribers_count
 *
 * @property Categories $category
 * @property Videos[] $videos
 */
class Channels extends \yii\db\ActiveRecord
{
    public $timeframeExist;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'channels';
    }

    /**
     * Проверка корректности критерия удаления.
     *
     * @param $attribute
     * @param $params
     */
    public function timeframeCheck($attribute, $params)
    {
        if ($this->$attribute)
            if ($this->flush_timeframe == '' || $this->flush_count <= 0)
                $this->addError($attribute, 'Укажите корректные данные для критерия удаления.');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'url', 'channel_link', 'category_id'], 'required'],
            [['url'], 'string'],
            [['channel_link'], 'unique'],
            [['category_id'], 'integer'],
            [['name', 'image_url'], 'string', 'max' => 255],
            [['channel_link'], 'string', 'max' => 128],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Categories::className(), 'targetAttribute' => ['category_id' => 'id']],
            [['flush_count'], 'integer'],
            [['flush_timeframe'], 'string', 'max' => 20],
            [['timeframeExist'], 'boolean'],
            [['timeframeExist'], 'timeframeCheck'],
            [['load_last_days', 'subscribers_count'], 'integer'],
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
            'url' => 'URL канала',
            'channel_link' => 'ID канала',
            'image_url' => 'Картинка канала',
            'category_id' => 'Рубрика',
            'flush_timeframe' => 'Период очистки',
            'flush_count' => 'Минимальное количество просмотров',
            'timeframeExist' => 'Особый критерий удаления',
            'videos' => 'Видеозаписей',
            'load_last_days' => 'Загружать видео за период (дней)',
            'subscribers_count' => 'Количество подписчиков',
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->timeframeExist = ($this->flush_timeframe != '') && ($this->flush_count > 0);
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if (!$this->timeframeExist) {
            $this->flush_timeframe = null;
            $this->flush_count = null;
        }

        if ($this->load_last_days == 0)
            $this->load_last_days = null;

        return parent::beforeValidate();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Categories::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVideos()
    {
        return $this->hasMany(Videos::className(), ['channel_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return ChannelsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ChannelsQuery(get_called_class());
    }

    /**
     * Получение данных о канале пользователя по ссылке.
     *
     * @param $url
     * @return array|mixed
     */
    public static function queryData($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
            return ['error' => 'Данный URL не является верным.'];

        if (preg_match('#/user/(.+)/#i', $url, $matches) || preg_match('#/user/(.+)$#i', $url, $matches)) {
            $userId = $matches[ 1 ];

            $result = YoutubeAPI::query('channels', ['forUsername' => $userId], ['snippet', 'statistics']);
        } else if (preg_match('#/channel/(.+)/#i', $url, $matches) || preg_match('#/channel/(.+)$#i', $url, $matches)) {
            $channelId = $matches[ 1 ];

            $result = YoutubeAPI::query('channels', ['id' => $channelId], ['snippet', 'statistics']);
        } else {
            return ['error' => 'Данный URL не является ссылкой на канал пользователя.'];
        }

        if (!empty($result))
            return reset(array_map(function($item) {
                return [
                    'id' => $item[ 'id' ],
                    'name' => $item[ 'snippet' ][ 'title' ],
                    'image' => $item[ 'snippet' ][ 'thumbnails' ][ 'default' ][ 'url' ],
                    'subscribers_count' => $item[ 'statistics' ][ 'subscriberCount' ],
                    'duplicate' => Channels::find()->where(['channel_link' => $item[ 'id' ]])->count() > 0
                ];
            }, $result));
        else
            return ['error' => 'Канал не найден.'];
    }

    /**
     * Поиск каналов по запросу.
     *
     * @param $query
     * @param $count
     * @param $subscribers
     * @return array|mixed
     * @internal param $url
     */
    public static function searchData($query, $count, $subscribers)
    {
        ob_start();

        $pageCount = ceil($count / 50);

        if ($pageCount <= 0)
            return [
                'error' => 'Укажите количество пунктов поисковой выдачи не менее 1.'
            ];

        YoutubeAPI::$iterationsCount = $pageCount;
        YoutubeAPI::$enableDateFilter = false;
        $response = array_slice(YoutubeAPI::query('search', [
            'q' => $query,
            'relevanceLanguage' => Yii::$app->language,
            'type' => 'video',
        ], ['snippet'], YoutubeAPI::QUERY_PAGES), 0, $count);

        // собираем channelId всех каналов
        $newChannelsId = [];
        foreach ($response as $item)
            $newChannelsId[ $item[ 'snippet' ][ 'channelId' ] ] = [
                'url' => 'https://youtube.com/channel/' . $item[ 'snippet' ][ 'channelId' ],
                'id' => $item[ 'snippet' ][ 'channelId' ],
                'exists' => 0,
            ];

        $responses = HighloadAPI::query('channels', ['id' => array_keys($newChannelsId)], ['snippet', 'statistics'], YoutubeAPI::QUERY_MULTIPLE);

        ob_end_clean(); // для обхода отладки HighloadAPI

        foreach ($responses as $response) {
            $response = unserialize(gzuncompress($response));

            foreach ($response as $item) {
                if (!isset($newChannelsId[ $item[ 'id' ] ]))
                    continue;

                $newChannelsId[ $item[ 'id' ] ][ 'name' ] = $item[ 'snippet' ][ 'title' ];
                $newChannelsId[ $item[ 'id' ] ][ 'image_url' ] = $item[ 'snippet' ][ 'thumbnails' ][ 'medium' ][ 'url' ];
                $newChannelsId[ $item[ 'id' ] ][ 'subscribers_count' ] = $item[ 'statistics' ][ 'subscriberCount' ];
            }
        }

        $newChannelsId = array_filter($newChannelsId, function($item) use ($subscribers) {
            return $item[ 'subscribers_count' ] >= $subscribers;
        });

        // помечаем каналы, существующие в базе данных
        $channels = Channels::find()->all();
        foreach ($channels as $channel)
            if (isset($newChannelsId[ $channel->channel_link ]))
                $newChannelsId[ $channel->channel_link ][ 'exists' ] = 1;

        // сортируем в порядке убывания количества подписчиков
        usort($newChannelsId, function($a, $b) {
            return $b[ 'subscribers_count' ] - $a[ 'subscribers_count' ];
        });

        if (empty($newChannelsId))
            return ['error' => 'По вашему запросу не найдено ни одного канала.'];

        return [
            'items' => $newChannelsId
        ];
    }
}
