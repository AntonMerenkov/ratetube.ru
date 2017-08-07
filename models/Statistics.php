<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

// TODO: удалить таблицу statistics и сделать этот класс хэлпером

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
     * @var array Названия таблиц.
     */
    public static $tableModels = [
        self::QUERY_TIME_MINUTE => 'StatisticsMinute',
        self::QUERY_TIME_HOUR => 'StatisticsHour',
        self::QUERY_TIME_DAY => 'StatisticsDay',
        self::QUERY_TIME_WEEK => 'StatisticsWeek',
        self::QUERY_TIME_MONTH => 'StatisticsMonth',
    ];

    /**
     * @var array Названия временных интервалов.
     */
    public static $timeTypes = [
        self::QUERY_TIME_MINUTE => 'за 10 минут',
        self::QUERY_TIME_HOUR => 'за час',
        self::QUERY_TIME_DAY => 'за день',
        self::QUERY_TIME_WEEK => 'за неделю',
        self::QUERY_TIME_MONTH => 'за месяц',
    ];

    /**
     * @var array Интервал между текущими и предыдущими данными.
     */
    public static $timeDiffs = [
        self::QUERY_TIME_MINUTE => 600,
        self::QUERY_TIME_HOUR => 3600,
        self::QUERY_TIME_DAY => 86400,
        self::QUERY_TIME_WEEK => 86400 * 7,
        self::QUERY_TIME_MONTH => 86400 * 30,
    ];

    /**
     * @var array Интервал для добавления данных в БД.
     */
    public static $appendInterval = [
        self::QUERY_TIME_MINUTE => 60,
        self::QUERY_TIME_HOUR => 300,
        self::QUERY_TIME_DAY => 1800,
        self::QUERY_TIME_WEEK => 10800,
        self::QUERY_TIME_MONTH => 43200,
    ];

    /**
     * Типы сортировки.
     */
    const SORT_TYPE_VIEWS_DIFF = 'views_diff';
    const SORT_TYPE_LIKES_DIFF = 'likes_diff';
    const SORT_TYPE_DISLIKES_DIFF = 'dislikes_diff';
    const SORT_TYPE_VIEWS = 'views';

    /**
     * @var array Названия типов сортировки.
     */
    public static $sortingTypes = [
        self::SORT_TYPE_VIEWS_DIFF => 'Просмотры',
        self::SORT_TYPE_LIKES_DIFF => 'Лайки',
        self::SORT_TYPE_DISLIKES_DIFF => 'Дизлайки',
        self::SORT_TYPE_VIEWS => 'Просмотры',
    ];

    /**
     * Константы для сохранения данных в сессиях.
     */
    const TIME_SESSION_KEY = 'time-type';
    const SORT_SESSION_KEY = 'sort-type';
    const PAGINATION_ROW_COUNT = 50;

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

    /**
     * Получение статистики по видео с постраничной разбивкой
     *
     * @param int $page
     * @param array $filter
     * @return array
     */
    public static function getStatistics($page = 1, $filter = [])
    {
        $timeType = Yii::$app->session->get(Statistics::TIME_SESSION_KEY, Statistics::QUERY_TIME_HOUR);
        $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $timeType ];
        $tableName = $tableModel::tableName();
        $sortType = Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF);

        $lastDate = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableName)->queryScalar();
        $prevDate = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableName . ' where datetime <= "' .
            date('Y-m-d H:i:s', strtotime($lastDate) - Statistics::$timeDiffs[ $timeType ]) . '"')->queryScalar();

        if (is_null($prevDate))
            $prevDate = $lastDate;

        $time = microtime(true);

        $cacheId = 'statistics-' . date('Y-m-d-H-i-s', strtotime($lastDate)) . '-' . (int) $filter[ 'category_id' ] . '-' . $sortType . '-' . $timeType;

        // 3 отдельных запроса получаются быстрее единого
        $videoSql = "SELECT v.id, v.name, v.video_link
                      FROM videos v
                      " . ($filter[ 'category_id' ] > 0 ? "LEFT JOIN channels c ON c.id = v.channel_id
                      WHERE c.category_id = " . $filter[ 'category_id' ] : "");
        $lastTimeSql = "SELECT s.video_id, s.views, s.likes, s.dislikes
                        FROM " . $tableName . " s
                        WHERE datetime = '" . $lastDate . "'";
        $prevTimeSql = "SELECT s.video_id, s.views, s.likes, s.dislikes
                        FROM " . $tableName . " s
                        WHERE datetime = '" . $prevDate . "'";

        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($videoSql, $lastTimeSql, $prevTimeSql, $sortType) {
            $videoData = Yii::$app->db->createCommand($videoSql)->queryAll();
            $lastTimeData = Yii::$app->db->createCommand($lastTimeSql)->queryAll();
            $prevTimeData = Yii::$app->db->createCommand($prevTimeSql)->queryAll();

            $videoData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $videoData), $videoData);
            $lastTimeData = array_combine(array_map(function($item) {
                return $item[ 'video_id' ];
            }, $lastTimeData), $lastTimeData);
            $prevTimeData = array_combine(array_map(function($item) {
                return $item[ 'video_id' ];
            }, $prevTimeData), $prevTimeData);

            foreach ($videoData as $id => $value) {
                $videoData[ $id ][ 'views' ] = $lastTimeData[ $id ][ 'views' ];
                $videoData[ $id ][ 'views_diff' ] = ($lastTimeData[ $id ][ 'views' ] > 0 && $prevTimeData[ $id ][ 'views' ] > 0 ?
                    $lastTimeData[ $id ][ 'views' ] - $prevTimeData[ $id ][ 'views' ] : 0);
                $videoData[ $id ][ 'likes_diff' ] = ($lastTimeData[ $id ][ 'likes' ] > 0 && $prevTimeData[ $id ][ 'likes' ] > 0 ?
                    $lastTimeData[ $id ][ 'likes' ] - $prevTimeData[ $id ][ 'likes' ] : 0);
                $videoData[ $id ][ 'dislikes_diff' ] = ($lastTimeData[ $id ][ 'dislikes' ] > 0 && $prevTimeData[ $id ][ 'dislikes' ] > 0 ?
                    $lastTimeData[ $id ][ 'dislikes' ] - $prevTimeData[ $id ][ 'dislikes' ] : 0);
            }

            usort($videoData, function($a, $b) use ($sortType) {
                if ($a[ $sortType ] != $b[ $sortType ])
                    return $b[ $sortType ] - $a[ $sortType ];
                else
                    return $b[ 'views' ] - $a[ 'views' ];
            });

            return $videoData;
        }, 600);

        $time = microtime(true) - $time;

        $count = count($data);
        $data = array_chunk($data, Statistics::PAGINATION_ROW_COUNT);
        $data = $data[ $page - 1 ];

        return [
            'data' => $data,
            'pagination' => [
                'count' => $count,
                'page' => $page,
                'pageCount' => ceil($count / Statistics::PAGINATION_ROW_COUNT)
            ],
            'time' => [
                'from' => date('d.m.Y H:i:s', strtotime($lastDate)),
                'to' => date('d.m.Y H:i:s', strtotime($prevDate)),
            ],
            'db' => [
                'query_time' => Yii::$app->formatter->asDecimal($time, 2),
                'sql' => $videoSql . "\n\n" . $lastTimeSql . "\n\n" . $prevTimeSql
            ]
        ];
    }

    /**
     * Получение статистики по занимаемому месту на диске.
     *
     * @return array
     */
    public static function getTableSizeData()
    {
        $dsn = array_map(function($item) {
            return explode('=', $item);
        }, explode(';', Yii::$app->db->dsn));
        $dsn = array_combine(array_map(function($item) {
            return $item[ 0 ];
        }, $dsn), array_map(function($item) {
            return $item[ 1 ];
        }, $dsn));

        return ArrayHelper::map(Yii::$app->db->createCommand("select TABLE_NAME, TABLE_COMMENT, DATA_LENGTH, INDEX_LENGTH
from information_schema.TABLES
where TABLE_SCHEMA = '" . $dsn[ 'dbname' ] . "'")->queryAll(), 'TABLE_NAME', function($item) {
            return $item;
        });
    }
}
