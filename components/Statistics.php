<?php

namespace app\components;

use app\models\StatisticsMinute;
use app\models\Videos;
use Yii;
use yii\helpers\ArrayHelper;

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
     * @var array Цвета для отображения в графиках.
     */
    public static $timeColors = [
        self::QUERY_TIME_MINUTE => '#006098',
        self::QUERY_TIME_HOUR => '#0099a9',
        self::QUERY_TIME_DAY => '#009579',
        self::QUERY_TIME_WEEK => '#dbaa00',
        self::QUERY_TIME_MONTH => '#fa364a',
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
        self::QUERY_TIME_MINUTE => 300,
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
    const SORT_TYPE_POSITION_DIFF = 'position_diff';

    /**
     * @var array Названия типов сортировки.
     */
    public static $sortingTypes = [
        self::SORT_TYPE_VIEWS_DIFF => 'Просмотры',
        self::SORT_TYPE_LIKES_DIFF => 'Лайки',
        self::SORT_TYPE_DISLIKES_DIFF => 'Дизлайки',
        self::SORT_TYPE_VIEWS => 'Просмотры',
        self::SORT_TYPE_POSITION_DIFF => 'Позиция',
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
                    'part' => 'statistics,liveStreamingDetails',
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
                foreach ($response[ 'items' ] as $item) {
                    $result[ $item[ 'id' ] ] = $item[ 'statistics' ];

                    if (isset($item[ 'liveStreamingDetails' ][ 'concurrentViewers' ]))
                        $result[ $item[ 'id' ] ][ 'viewers' ] = $item[ 'liveStreamingDetails' ][ 'concurrentViewers' ];
                }
        }

        return $result;
    }

    /**
     * Получение статистики по видео с постраничной разбивкой
     *
     * @param int $page
     * @param array $filter
     *      'category_id' - Фильтр по категории
     *      'channel_id' - Фильтр по каналу
     *      'timeType' - Принудительная установка таймфрейма
     *      'sortType' - Принудительная установка сортировки
     *      'fullData' - Выдача без постраничной разбивки (boolean)
     * @return array
     */
    public static function getStatistics($page = 1, $filter = [])
    {
        // выбираем нужную таблицу
        $timeType = isset($filter[ 'timeType' ]) ? $filter[ 'timeType' ] :
            Yii::$app->session->get(Statistics::TIME_SESSION_KEY, Statistics::QUERY_TIME_HOUR);
        $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $timeType ];
        $tableName = $tableModel::tableName();
        $sortType = isset($filter[ 'sortType' ]) ? $filter[ 'sortType' ] :
            Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF);

        $lastDate = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableName)->queryScalar();
        $prevDate = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableName . ' where datetime <= "' .
            date('Y-m-d H:i:s', strtotime($lastDate) - Statistics::$timeDiffs[ $timeType ]) . '"')->queryScalar();
        if (isset($prevDate))
            $prevDate2 = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableName . ' where datetime <= "' .
                date('Y-m-d H:i:s', strtotime($prevDate) - Statistics::$timeDiffs[ $timeType ]) . '"')->queryScalar();

        if (is_null($prevDate))
            $prevDate = $lastDate;
        if (!isset($prevDate2))
            $prevDate2 = $lastDate;

        $time = microtime(true);

        $cacheId = 'statistics-' . implode('-', [
                date('Y-m-d-H-i-s', strtotime($lastDate)),
                (int) $filter[ 'category_id' ],
                (int) $filter[ 'channel_id' ],
                $sortType,
                $timeType
        ]);

        // 5 отдельных запросов получаются быстрее единого
        $videoSql = "SELECT v.id, v.name, v.video_link, v.image_url, v.channel_id
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
        $prevTimeSql2 = "SELECT s.video_id, s.views, s.likes, s.dislikes
                        FROM " . $tableName . " s
                        WHERE datetime = '" . $prevDate2 . "'";

        // для демо-режима не использовать кэш
        if (Yii::$app->controller->route == 'statistics/index')
            Yii::$app->cache->delete($cacheId);

        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($videoSql, $channelSql, $lastTimeSql, $prevTimeSql, $prevTimeSql2, $sortType, $filter, $cacheId) {
            Yii::beginProfile('Генерация статистики [' . $cacheId . ']');

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

            if (!$filter[ 'fullData' ]) {
                $prevTimeData2 = Yii::$app->db->createCommand($prevTimeSql2)->queryAll();
                $prevTimeData2 = array_combine(array_map(function($item) {
                    return $item[ 'video_id' ];
                }, $prevTimeData2), $prevTimeData2);
            }

            foreach ($videoData as $id => $value) {
                $videoData[ $id ][ 'channel' ] = $channelData[ $videoData[ $id ][ 'channel_id' ] ];
                unset($videoData[ $id ][ 'channel_id' ]);

                $videoData[ $id ][ 'views' ] = $lastTimeData[ $id ][ 'views' ];
                $videoData[ $id ][ 'views_old' ] = $prevTimeData[ $id ][ 'views' ];
                $videoData[ $id ][ 'views_diff' ] = ($lastTimeData[ $id ][ 'views' ] > 0 && $prevTimeData[ $id ][ 'views' ] > 0 ?
                    $lastTimeData[ $id ][ 'views' ] - $prevTimeData[ $id ][ 'views' ] : 0);
                $videoData[ $id ][ 'likes_diff' ] = ($lastTimeData[ $id ][ 'likes' ] > 0 && $prevTimeData[ $id ][ 'likes' ] > 0 ?
                    $lastTimeData[ $id ][ 'likes' ] - $prevTimeData[ $id ][ 'likes' ] : 0);
                $videoData[ $id ][ 'dislikes_diff' ] = ($lastTimeData[ $id ][ 'dislikes' ] > 0 && $prevTimeData[ $id ][ 'dislikes' ] > 0 ?
                    $lastTimeData[ $id ][ 'dislikes' ] - $prevTimeData[ $id ][ 'dislikes' ] : 0);

                // для вычисления позиции
                if (!$filter[ 'fullData' ]) {
                    $videoData[ $id ][ 'views2' ] = $prevTimeData[ $id ][ 'views' ];
                    $videoData[ $id ][ 'views_diff2' ] = ($prevTimeData[ $id ][ 'views' ] > 0 && $prevTimeData2[ $id ][ 'views' ] > 0 ?
                        $prevTimeData[ $id ][ 'views' ] - $prevTimeData2[ $id ][ 'views' ] : 0);
                }
            }

            Yii::beginProfile('Сортировка');

            // вычисление позиций по views_diff (для полных данных - не вычислять)
            if (!$filter[ 'fullData' ]) {
                usort($videoData, function($a, $b) {
                    if ($a[ 'views_diff' ] != $b[ 'views_diff' ])
                        return $b[ 'views_diff' ] - $a[ 'views_diff' ];
                    else
                        return $b[ 'views' ] - $a[ 'views' ];
                });
                $lastPositions = array_map(function($item) {
                    return $item[ 'id' ];
                }, $videoData);
                usort($videoData, function($a, $b) {
                    if ($a[ 'views_diff2' ] != $b[ 'views_diff2' ])
                        return $b[ 'views_diff2' ] - $a[ 'views_diff2' ];
                    else
                        return $b[ 'views2' ] - $a[ 'views2' ];
                });
                $prevPositions = array_map(function($item) {
                    return $item[ 'id' ];
                }, $videoData);

                Yii::beginProfile('Сопоставление позиций');
                $lastPositions = array_flip($lastPositions);
                $prevPositions = array_flip($prevPositions);
                foreach ($videoData as $id => $value) {
                    if (isset($videoData[ $id ][ 'views2' ]) && isset($videoData[ $id ][ 'views_diff2' ]))
                        $videoData[ $id ][ 'position_diff' ] = $prevPositions[ $videoData[ $id ][ 'id' ] ] - $lastPositions[ $videoData[ $id ][ 'id' ] ];
                    else
                        $videoData[ $id ][ 'position_diff' ] = 0;

                    unset($videoData[ $id ][ 'views2' ]);
                    unset($videoData[ $id ][ 'views_diff2' ]);
                }
                Yii::endProfile('Сопоставление позиций');
            }

            usort($videoData, function($a, $b) use ($sortType) {
                if ($a[ $sortType ] != $b[ $sortType ])
                    return $b[ $sortType ] - $a[ $sortType ];
                else
                    return $b[ 'views' ] - $a[ 'views' ];
            });

            Yii::endProfile('Сортировка');

            Yii::endProfile('Генерация статистики [' . $cacheId . ']');

            return $videoData;
        }, 600);

        // если включен поиск, то фильтруем данные
        if (isset($filter[ 'query' ])) {
            $videoIds = Videos::searchByQuery($filter[ 'query' ]);
            $data = array_values(array_filter($data, function($item) use ($videoIds) {
                return in_array($item[ 'id' ], $videoIds);
            }));
        }

        $time = microtime(true) - $time;

        $count = count($data);
        if (!$filter[ 'fullData' ]) {
            $data = array_chunk($data, Statistics::PAGINATION_ROW_COUNT);
            $data = $data[ $page - 1 ];
        }

        return [
            'data' => $data,
            'pagination' => [
                'count' => $count,
                'page' => $page,
                'pageCount' => $filter[ 'fullData' ] ? 1 : ceil($count / Statistics::PAGINATION_ROW_COUNT)
            ],
            'time' => [
                'from' => date('d.m.Y H:i:s', strtotime($lastDate)),
                'to' => date('d.m.Y H:i:s', strtotime($prevDate)),
            ],
            'db' => [
                'query_time' => Yii::$app->formatter->asDecimal($time, 2),
                'sql' => self::formatSql($videoSql) . "\n\n" . self::formatSql($channelSql) . "\n\n" . self::formatSql($lastTimeSql) . "\n\n" . self::formatSql($prevTimeSql),
                'cache_id' => $cacheId,
                'sort_type' => $sortType,
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

    /**
     * Получение списка видео в эфире.
     * Список составляется из временного интервала minute.
     *
     * @return array
     */
    public static function getStreaming()
    {
        $lastDate = Yii::$app->db->createCommand('select MAX(datetime) from ' . StatisticsMinute::tableName())->queryScalar();

        $cacheId = 'streaming-' . implode('-', [
            date('Y-m-d-H-i-s', strtotime($lastDate)),
        ]);

        $lastTimeSql = "SELECT s.video_id, s.viewers
                        FROM " . StatisticsMinute::tableName() . " s
                        WHERE viewers IS NOT NULL AND datetime = '" . $lastDate . "'";

        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($lastTimeSql) {
            $lastTimeData = Yii::$app->db->createCommand($lastTimeSql)->queryAll();

            if (empty($lastTimeData))
                return [];

            $videoIds = array_map(function($item) {
                return $item[ 'video_id' ];
            }, $lastTimeData);

            $videoSql = "SELECT v.id, v.name, v.video_link, v.image_url, v.channel_id
                      FROM videos v
                      WHERE v.id IN (" . implode(',', $videoIds) . ")";

            $channelSql = "SELECT c.id, c.name, c.url, c.image_url FROM channels c";

            $videoData = Yii::$app->db->createCommand($videoSql)->queryAll();
            $channelData = Yii::$app->db->createCommand($channelSql)->queryAll();

            $videoData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $videoData), $videoData);
            $channelData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $channelData), $channelData);
            $lastTimeData = array_combine(array_map(function($item) {
                return $item[ 'video_id' ];
            }, $lastTimeData), $lastTimeData);

            foreach ($videoData as $id => $value) {
                $videoData[ $id ][ 'channel' ] = $channelData[ $videoData[ $id ][ 'channel_id' ] ];
                unset($videoData[ $id ][ 'channel_id' ]);

                $videoData[ $id ][ 'viewers' ] = $lastTimeData[ $id ][ 'viewers' ];
            }

            // сортировка
            usort($videoData, function($a, $b) {
                return $b[ 'viewers' ] - $a[ 'viewers' ];
            });

            return $videoData;
        }, 1); // TODO: заменить на 600

        return $data;
    }
}
