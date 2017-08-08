<?php

namespace app\components;

use Yii;
use yii\helpers\ArrayHelper;

// TODO: удалить таблицу statistics и сделать этот класс хэлпером

/**
 * Класс для получения данных по статистике.
 */
class Statistics
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
        self::QUERY_TIME_MINUTE => '10 минут',
        self::QUERY_TIME_WEEK => 'неделя',
        self::QUERY_TIME_HOUR => 'час',
        self::QUERY_TIME_DAY => 'день',
        self::QUERY_TIME_MONTH => 'месяц',
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

            if (isset($response[ 'error' ])) {
                return [
                    'error' => $response[ 'error' ][ 'errors' ][ 0 ][ 'message' ]
                ];
            }

            if (isset($response[ 'items' ]))
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
        // выбираем нужную таблицу
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

        $cacheId = 'statistics-' . implode('-', [
                date('Y-m-d-H-i-s', strtotime($lastDate)),
                (int) $filter[ 'category_id' ],
                (int) $filter[ 'channel_id' ],
                $sortType,
                $timeType
        ]);

        // 4 отдельных запроса получаются быстрее единого
        $videoSql = "SELECT v.id, v.name, v.video_link, v.channel_id
                      FROM videos v
                      " . ($filter[ 'category_id' ] > 0 ? "LEFT JOIN channels c ON c.id = v.channel_id" : "") . "
                      WHERE v.active = 1
                      " . ($filter[ 'category_id' ] > 0 ? "AND c.category_id = " . $filter[ 'category_id' ] : "") . "
                      " . ($filter[ 'channel_id' ] > 0 ? "AND v.channel_id = " . $filter[ 'channel_id' ] : "");
        $channelSql = "SELECT c.id, c.name, c.url, c.image_url FROM channels c";
        $lastTimeSql = "SELECT s.video_id, s.views, s.likes, s.dislikes
                        FROM " . $tableName . " s
                        WHERE datetime = '" . $lastDate . "'";
        $prevTimeSql = "SELECT s.video_id, s.views, s.likes, s.dislikes
                        FROM " . $tableName . " s
                        WHERE datetime = '" . $prevDate . "'";

        // для демо-режима не использовать кэш
        if (Yii::$app->controller->route == 'statistics/index')
            Yii::$app->cache->delete($cacheId);

        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($videoSql, $channelSql, $lastTimeSql, $prevTimeSql, $sortType) {
            $videoData = Yii::$app->db->createCommand($videoSql)->queryAll();
            $channelData = Yii::$app->db->createCommand($channelSql)->queryAll();
            $lastTimeData = Yii::$app->db->createCommand($lastTimeSql)->queryAll();
            $prevTimeData = Yii::$app->db->createCommand($prevTimeSql)->queryAll();

            $videoData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $videoData), $videoData);
            $channelData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $channelData), $channelData);
            $lastTimeData = array_combine(array_map(function($item) {
                return $item[ 'video_id' ];
            }, $lastTimeData), $lastTimeData);
            $prevTimeData = array_combine(array_map(function($item) {
                return $item[ 'video_id' ];
            }, $prevTimeData), $prevTimeData);

            foreach ($videoData as $id => $value) {
                $videoData[ $id ][ 'channel' ] = $channelData[ $videoData[ $id ][ 'channel_id' ] ];
                unset($videoData[ $id ][ 'channel_id' ]);

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
                'sql' => self::formatSql($videoSql) . "\n\n" . self::formatSql($channelSql) . "\n\n" . self::formatSql($lastTimeSql) . "\n\n" . self::formatSql($prevTimeSql)
            ]
        ];
    }

    /**
     * Форматирование текста SQL-запроса для вывода на экран.
     *
     * @param $sql
     * @return string
     */
    private static function formatSql($sql)
    {
        return trim(preg_replace('/^\s+/m', '', $sql));
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

        $tables = ArrayHelper::map(Yii::$app->db->createCommand("select TABLE_NAME, TABLE_COMMENT, DATA_LENGTH, INDEX_LENGTH
from information_schema.TABLES
where TABLE_SCHEMA = '" . $dsn[ 'dbname' ] . "'")->queryAll(), 'TABLE_NAME', function($item) {
            if (preg_match('/^statistics_/i', $item[ 'TABLE_NAME' ])) {
                $item[ 'MIN_DATE' ] = Yii::$app->db->createCommand('SELECT MIN(datetime) from ' . $item[ 'TABLE_NAME' ])->queryScalar();

                if (!is_null($item[ 'MIN_DATE' ]))
                    $item[ 'DATE_DIFF' ] = time() - strtotime($item[ 'MIN_DATE' ]);
            }

            return $item;
        });

        uksort($tables, function($a, $b) {
            if (preg_match('/^statistics_(.+)/', $a, $aMatches) && preg_match('/^statistics_(.+)/', $b, $bMatches))
                return array_search($aMatches[ 1 ], array_keys(self::$tableModels)) - array_search($bMatches[ 1 ], array_keys(self::$tableModels));
            else
                return strcmp($a, $b);
        });

        return $tables;
    }
}
