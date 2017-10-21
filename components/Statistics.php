<?php

namespace app\components;

use app\models\StatisticsMinute;
use app\models\Videos;
use SplPriorityQueue;
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
        self::QUERY_TIME_HOUR => 'час',
        self::QUERY_TIME_DAY => 'день',
        self::QUERY_TIME_WEEK => 'неделя',
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
    const CACHE_HISTORY_KEY = 'cache-history';

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
     *      'noCache' - Не использовать кэш
     *      'findCached' - Выбрать предыдущие данные из кэша
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

        // если указано "Выбрать из кэша"
        if ($filter[ 'findCached' ] && !Yii::$app->cache->exists($cacheId)) {
            $cacheHistory = Yii::$app->cache->get(self::CACHE_HISTORY_KEY);

            if (is_array($cacheHistory) && isset($cacheHistory[ $timeType ]) && !empty($cacheHistory[ $timeType ]))
                foreach ($cacheHistory[ $timeType ] as $newCacheId)
                    if (Yii::$app->cache->exists($newCacheId)) {
                        $cacheId = $newCacheId;
                        //echo "Найден кэш " . $newCacheId . "\n";
                        break;
                    }
        }

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
        $positionsSql = "SELECT p.video_id, p.position FROM positions p";

        // для демо-режима не использовать кэш
        if ($filter[ 'noCache' ])
            Yii::$app->cache->delete($cacheId);

        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($videoSql, $channelSql, $lastTimeSql, $prevTimeSql, $prevTimeSql2, $positionsSql, $sortType, $filter, $cacheId) {
            Yii::beginProfile('Генерация статистики [' . $cacheId . ']');

            $videoData = Yii::$app->db->createCommand($videoSql)->queryAll();
            $channelData = Yii::$app->db->createCommand($channelSql)->queryAll();
            $lastTimeData = Yii::$app->db->createCommand($lastTimeSql)->queryAll();
            $prevTimeData = Yii::$app->db->createCommand($prevTimeSql)->queryAll();
            $positionsData = Yii::$app->db->createCommand($positionsSql)->queryAll();

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

            Yii::beginProfile('Составление массива videoData');
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
            Yii::endProfile('Составление массива videoData');

            if (!empty($positionsData)) {
                $positionsData = array_filter($positionsData, function($item) use ($videoData) {
                    return isset($videoData[ $item[ 'video_id' ] ]);
                });

                // позиции видео (по 1 видео выбирать случайно)
                $positionsData = array_reduce($positionsData, function($carry, $item) {
                    $carry[ $item[ 'position' ] - 1 ][] = $item[ 'video_id' ];

                    return $carry;
                }, []);

                foreach ($positionsData as $position => $items) {
                    $positionsData[ $position ] = $videoData[ $items[ array_rand($items) ] ];
                    $positionsData[ $position ][ 'ad' ] = 1;
                }
            }

            Yii::beginProfile('Сортировка');

            // вычисление позиций по views_diff (для полных данных - не вычислять)
            if (!$filter[ 'fullData' ]) {
                $videoIds = array_map(function($item) {
                    return [
                        $item[ 'views_diff' ],
                        $item[ 'views' ]
                    ];
                }, $videoData);

                uasort($videoIds, function($a, $b) {
                    if ($a[ 0 ] != $b[ 0 ])
                        return $b[ 0 ] - $a[ 0 ];
                    else
                        return $b[ 1 ] - $a[ 1 ];
                });

                $videoIds = array_keys($videoIds);

                $lastPositions = array_map(function($item) use ($videoData) {
                    return $videoData[ $item ][ 'id' ];
                }, $videoIds);

                $videoIds = array_map(function($item) {
                    return [
                        $item[ 'views_diff2' ],
                        $item[ 'views2' ]
                    ];
                }, $videoData);

                uasort($videoIds, function($a, $b) {
                    if ($a[ 0 ] != $b[ 0 ])
                        return $b[ 0 ] - $a[ 0 ];
                    else
                        return $b[ 1 ] - $a[ 1 ];
                });

                $videoIds = array_keys($videoIds);

                $prevPositions = array_map(function($item) use ($videoData) {
                    return $videoData[ $item ][ 'id' ];
                }, $videoIds);

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

            // сортируем ограниченный объем данных, затем map
            $videoIds = array_map(function($item) use ($sortType) {
                return [
                    $item[ $sortType ],
                    $item[ 'views' ]
                ];
            }, $videoData);

            uasort($videoIds, function($a, $b) {
                if ($a[ 0 ] != $b[ 0 ])
                    return $b[ 0 ] - $a[ 0 ];
                else
                    return $b[ 1 ] - $a[ 1 ];
            });

            $videoIds = array_keys($videoIds);

            $videoData = array_map(function($item) use ($videoData) {
                return $videoData[ $item ];
            }, $videoIds);

            Yii::endProfile('Сортировка');

            if (!empty($positionsData)) {
                Yii::beginProfile('Вставка позиций');

                // удаляем видео с позициями из общего списка
                $positionedVideoIds = array_map(function($item) {
                    return $item[ 'id' ];
                }, $positionsData);

                $videoData = array_filter($videoData, function($item) use ($positionedVideoIds) {
                    return !in_array($item[ 'id' ], $positionedVideoIds);
                });

                // вставляем видео на заданные позиции
                foreach ($positionsData as $position => $value)
                    $videoData = array_merge(array_slice($videoData, 0, $position), [$value], array_slice($videoData, $position));

                Yii::endProfile('Вставка позиций');
            }

            Yii::endProfile('Генерация статистики [' . $cacheId . ']');

            return $videoData;
        }, 86400);

        // добавляем ID кэша в массив последних кешей
        $cacheHistory = Yii::$app->cache->get(self::CACHE_HISTORY_KEY);
        if ($cacheHistory === false)
            $cacheHistory = [];
        if (!isset($cacheHistory[ $timeType ]))
            $cacheHistory[ $timeType ] = [];
        if (!in_array($cacheId, $cacheHistory[ $timeType ])) {
            array_unshift($cacheHistory[ $timeType ], $cacheId);
            Yii::$app->cache->set(self::CACHE_HISTORY_KEY, $cacheHistory);
        }

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
            'data' => is_array($data) ? $data : [],
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
        }, 600);

        return $data;
    }
}
