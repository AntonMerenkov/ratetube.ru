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

    public static $timeTypes = [
        self::QUERY_TIME_MINUTE => 'за 10 минут',
        self::QUERY_TIME_HOUR => 'за час',
        self::QUERY_TIME_DAY => 'за день',
        self::QUERY_TIME_WEEK => 'за неделю',
        self::QUERY_TIME_MONTH => 'за месяц',
    ];

    public static $timeDiffs = [
        self::QUERY_TIME_MINUTE => 600,
        self::QUERY_TIME_HOUR => 3600,
        self::QUERY_TIME_DAY => 86400,
        self::QUERY_TIME_WEEK => 86400 * 7,
        self::QUERY_TIME_MONTH => 86400 * 30,
    ];

    const SORT_TYPE_VIEWS_DIFF = 'views_diff';
    const SORT_TYPE_LIKES_DIFF = 'likes_diff';
    const SORT_TYPE_DISLIKES_DIFF = 'dislikes_diff';
    const SORT_TYPE_VIEWS = 'views';

    public static $sortingTypes = [
        self::SORT_TYPE_VIEWS_DIFF => 'Просмотры',
        self::SORT_TYPE_LIKES_DIFF => 'Лайки',
        self::SORT_TYPE_DISLIKES_DIFF => 'Дизлайки',
        self::SORT_TYPE_VIEWS => 'Просмотры',
    ];

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
        $sortType = Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF);

        $lastDate = Yii::$app->db->createCommand('select MAX(datetime) from statistics')->queryScalar();
        $prevDate = Yii::$app->db->createCommand('select MAX(datetime) from statistics where datetime <= "' .
            date('Y-m-d H:i:s', strtotime($lastDate) - Statistics::$timeDiffs[ $timeType ]) . '"')->queryScalar();

        $sql = "select SQL_CALC_FOUND_ROWS v.id, v.name, v.video_link, ls.views, (ls.views - ps.views) as views_diff, (ls.likes - ps.likes) as likes_diff, (ls.dislikes - ps.dislikes) as dislikes_diff 
                from (
                  select s.*
                  from statistics s
                  where datetime = '" . $lastDate . "'
                  order by " . str_replace('_diff', '', $sortType) . " DESC
                ) ls
                left join (
                  select s.*
                  from statistics s
                  where datetime = '" . $prevDate . "'
                  order by " . str_replace('_diff', '', $sortType) . " DESC
                ) ps on ps.video_id = ls.video_id
                left join videos v on v.id = ls.video_id
                " . ($filter[ 'category_id' ] > 0 ? "left join channels c on c.id = v.channel_id
                    where c.category_id = " . $filter[ 'category_id' ] : "") . "
                order by " . $sortType . " desc, ls." . str_replace('_diff', '', $sortType) . " desc
                limit " . (($page - 1) * Statistics::PAGINATION_ROW_COUNT) . ", " . Statistics::PAGINATION_ROW_COUNT;

        $sql = implode("\n", array_map("trim", explode("\n", $sql)));

        $time = microtime(true);

        $cacheId = 'statistics-' . date('Y-m-d-H-i-s', strtotime($lastDate)) . '-' . (int) $filter[ 'category_id' ] . '-' . $sortType . '-' . $timeType;

        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($sql) {
            return [
                'data' => Yii::$app->db->createCommand($sql)->queryAll(),
                'count' => Yii::$app->db->createCommand("SELECT FOUND_ROWS()")->queryScalar(),
            ];
        }, 600);

        $time = microtime(true) - $time;

        return [
            'data' => $data[ 'data' ],
            'pagination' => [
                'count' => $data[ 'count' ],
                'page' => $page,
                'pageCount' => ceil($data[ 'count' ] / Statistics::PAGINATION_ROW_COUNT)
            ],
            'time' => [
                'from' => date('d.m.Y H:i:s', strtotime($lastDate)),
                'to' => date('d.m.Y H:i:s', strtotime($prevDate)),
            ],
            'db' => [
                'query_time' => Yii::$app->formatter->asDecimal($time, 2),
                'sql' => $sql
            ]
        ];
    }
}
