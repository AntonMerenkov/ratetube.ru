<?php

namespace app\components;

use app\models\StatisticsMinute;
use app\models\Videos;
use SplPriorityQueue;
use Yii;
use yii\caching\DbDependency;
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
    const CACHE_HISTORY_KEY = 'cache-history'; // cacheHistory => [sortType][timeType] = [...]
    const CACHE_VIDEO_INFO_KEY = 'video-info';

    /**
     * Получение статистики по видео с постраничной разбивкой
     *
     * @param int $page
     * @param array $filter
     *      'category_id' - Фильтр по категории
     *      'channel_id' - Фильтр по каналу
     *      'query' - Фильтр по тэгу
     *      'timeType' - Принудительная установка таймфрейма
     *      'sortType' - Принудительная установка сортировки
     *      'fullData' - Выдача без постраничной разбивки (boolean) (не может быть использован с category_id, channel_id, query)
     *      'noCache' - Не использовать кэш
     *      'findCached' - Выбрать предыдущие данные из кэша
     * @return array
     */
    public static function getStatistics($page = 1, $filter = [])
    {
        Yii::beginProfile('Подготовка к генерации статистики');

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
                $sortType,
                $timeType
        ]);

        // если указано "Выбрать из кэша"
        if ($filter[ 'findCached' ] && !Yii::$app->cache->exists($cacheId)) {
            $cacheHistory = Yii::$app->cache->get(self::CACHE_HISTORY_KEY);

            if (is_array($cacheHistory) && isset($cacheHistory[ $sortType ][ $timeType ]) && !empty($cacheHistory[ $sortType ][ $timeType ]))
                foreach ($cacheHistory[ $sortType ][ $timeType ] as $newCacheId)
                    if (Yii::$app->cache->exists($newCacheId)) {
                        $cacheId = $newCacheId;
                        //echo "Найден кэш " . $newCacheId . "\n";
                        break;
                    }
        }

        // 5 отдельных запросов получаются быстрее единого
        /*$videoSql = "SELECT v.id, v.channel_id
                      FROM videos v
                      " . ($filter[ 'category_id' ] > 0 ? "LEFT JOIN channels c ON c.id = v.channel_id" : "") . "
                      WHERE v.active = 1
                      " . ($filter[ 'category_id' ] > 0 ? "AND c.category_id = " . $filter[ 'category_id' ] : "") . "
                      " . ($filter[ 'channel_id' ] > 0 ? "AND v.channel_id = " . $filter[ 'channel_id' ] : "");*/
        // отфильтровываем category_id и channel_id после обработки статистики
        $videoSql = "SELECT v.id, v.channel_id
                      FROM videos v
                      WHERE v.active = 1";
        $channelSql = "SELECT c.id, c.name, c.url, c.image_url, c.category_id FROM channels c";
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

        Yii::endProfile('Подготовка к генерации статистики');

        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($videoSql, $channelSql, $lastTimeSql, $prevTimeSql, $prevTimeSql2, $positionsSql, $sortType, $filter, $cacheId) {
            Yii::beginProfile('Генерация статистики [' . $cacheId . ']');

            Yii::beginProfile('Получение данных о видео');
            $videoData = Yii::$app->db->createCommand($videoSql)->queryAll();
            $videoData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $videoData), $videoData);
            Yii::endProfile('Получение данных о видео');

            Yii::beginProfile('Получение данных о каналах');
            $channelData = Yii::$app->db->createCommand($channelSql)->queryAll();
            $channelData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $channelData), $channelData);

            Yii::endProfile('Получение данных о каналах');

            Yii::beginProfile('Получение данных о последнем времени');
            $lastTimeData = Yii::$app->db->createCommand($lastTimeSql)->queryAll();
            $lastTimeData = array_combine(array_map(function($item) {
                return $item[ 'video_id' ];
            }, $lastTimeData), $lastTimeData);
            Yii::endProfile('Получение данных о последнем времени');

            Yii::beginProfile('Получение данных о предпоследнем времени');
            $prevTimeData = Yii::$app->db->createCommand($prevTimeSql)->queryAll();
            $prevTimeData = array_combine(array_map(function($item) {
                return $item[ 'video_id' ];
            }, $prevTimeData), $prevTimeData);

            foreach ($videoData as $id => $value) {
                $videoData[ $id ][ 'views' ] = $lastTimeData[ $id ][ 'views' ];
                $videoData[ $id ][ 'views_old' ] = $prevTimeData[ $id ][ 'views' ];
                $videoData[ $id ][ 'views_diff' ] = ($lastTimeData[ $id ][ 'views' ] > 0 && $prevTimeData[ $id ][ 'views' ] > 0 ?
                    $lastTimeData[ $id ][ 'views' ] - $prevTimeData[ $id ][ 'views' ] : 0);
                $videoData[ $id ][ 'likes_diff' ] = ($lastTimeData[ $id ][ 'likes' ] > 0 && $prevTimeData[ $id ][ 'likes' ] > 0 ?
                    $lastTimeData[ $id ][ 'likes' ] - $prevTimeData[ $id ][ 'likes' ] : 0);
                $videoData[ $id ][ 'dislikes_diff' ] = ($lastTimeData[ $id ][ 'dislikes' ] > 0 && $prevTimeData[ $id ][ 'dislikes' ] > 0 ?
                    $lastTimeData[ $id ][ 'dislikes' ] - $prevTimeData[ $id ][ 'dislikes' ] : 0);
            }

            unset($lastTimeData);
            Yii::endProfile('Получение данных о предпоследнем времени');

            if (!$filter[ 'fullData' ]) {
                Yii::beginProfile('Получение данных о предпредпоследнем времени');
                $prevTimeData2 = Yii::$app->db->createCommand($prevTimeSql2)->queryAll();
                $prevTimeData2 = array_combine(array_map(function($item) {
                    return $item[ 'video_id' ];
                }, $prevTimeData2), $prevTimeData2);

                foreach ($videoData as $id => $value) {
                    $videoData[ $id ][ 'views2' ] = $prevTimeData[ $id ][ 'views' ];
                    $videoData[ $id ][ 'views_diff2' ] = ($prevTimeData[ $id ][ 'views' ] > 0 && $prevTimeData2[ $id ][ 'views' ] > 0 ?
                        $prevTimeData[ $id ][ 'views' ] - $prevTimeData2[ $id ][ 'views' ] : 0);
                }

                unset($prevTimeData2);
                Yii::endProfile('Получение данных о предпредпоследнем времени');
            }

            unset($prevTimeData);

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

                unset($videoIds);

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

                unset($videoIds);

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

                unset($lastPositions);
                unset($prevPositions);
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

            Yii::beginProfile('Получение данных о позициях');
            $positionsData = Yii::$app->db->createCommand($positionsSql)->queryAll();

            if (!empty($positionsData)) {
                /*$positionsData = array_filter($positionsData, function($item) use ($videoData) {
                    return isset($videoData[ $item[ 'video_id' ] ]);
                });*/

                // позиции видео (по 1 видео выбирать случайно)
                $positionsData = array_reduce($positionsData, function($carry, $item) {
                    $carry[ $item[ 'position' ] - 1 ][] = $item[ 'video_id' ];

                    return $carry;
                }, []);

                Yii::beginProfile('Поиск позиций в общем списке');

                $videoIds = array_flip(array_map(function($item) {
                    return $item[ 'id' ];
                }, $videoData));

                foreach ($positionsData as $position => $items) {
                    // поиск в списке
                    if (isset($videoData[ $videoIds[ $items[ array_rand($items) ] ] ])) {
                        $positionsData[ $position ] = $videoData[ $videoIds[ $items[ array_rand($items) ] ] ];
                        $positionsData[ $position ][ 'ad' ] = 1;
                    } else {
                        $positionsData[ $position ] = [
                            'id' => $items[ array_rand($items) ],
                            'ad' => 1
                        ];
                    }
                }

                $positionsVideoData = Yii::$app->db->createCommand("SELECT v.id, v.channel_id
                      FROM videos v
                      WHERE v.id IN (" . implode(',', array_map(function($item) {
                        return $item[ 'id' ];
                    }, $positionsData)) .  ")")->queryAll();
                $positionsVideoData = array_combine(array_map(function($item) {
                    return $item[ 'id' ];
                }, $positionsVideoData), $positionsVideoData);

                foreach ($positionsData as $position => $item)
                    $positionsData[ $position ][ 'channel_id' ] = $positionsVideoData[ $item[ 'id' ] ][ 'channel_id' ];

                Yii::endProfile('Поиск позиций в общем списке');

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

                unset($positionsData);

                Yii::endProfile('Вставка позиций');
            }
            Yii::endProfile('Получение данных о позициях');

            Yii::endProfile('Генерация статистики [' . $cacheId . ']');

            return [
                'videoData' => $videoData,
                'channelData' => $channelData,
            ];
        }, 86400);

        // добавляем ID кэша в массив последних кешей
        Yii::beginProfile('Установка последнего кеша');
        $cacheHistory = Yii::$app->cache->get(self::CACHE_HISTORY_KEY);
        if ($cacheHistory === false)
            $cacheHistory = [];
        if (!isset($cacheHistory[ $sortType ][ $timeType ]))
            $cacheHistory[ $sortType ][ $timeType ] = [];
        if (!in_array($cacheId, $cacheHistory[ $sortType ][ $timeType ])) {
            array_unshift($cacheHistory[ $sortType ][ $timeType ], $cacheId);
            Yii::$app->cache->set(self::CACHE_HISTORY_KEY, $cacheHistory);
        }
        Yii::endProfile('Установка последнего кеша');

        $videoInfo = Yii::$app->cache->getOrSet(self::CACHE_VIDEO_INFO_KEY, function() {
            $data = Yii::$app->db->createCommand("SELECT v.id, v.name, v.video_link, v.image_url FROM videos v WHERE v.active = 1")->queryAll();
            $data = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $data), array_map(function($item) {
                unset($item[ 'id' ]);
                return $item;
            }, $data));

            return $data;
        }, null, new DbDependency(['sql' => 'SELECT MAX(id) FROM videos']));

        if (!$filter[ 'fullData' ]) {
            Yii::beginProfile('Фильтрация результатов по запросу');
            if ($filter[ 'category_id' ] > 0) {
                // если установлен канал - фильтруем данные
                if ($filter[ 'noCache' ])
                    Yii::$app->cache->delete($cacheId . '-cat=' . $filter[ 'category_id' ]);

                $data[ 'videoData' ] = Yii::$app->cache->getOrSet($cacheId . '-cat=' . $filter[ 'category_id' ], function() use ($data, $filter) {
                    $channelIds = array_filter($data[ 'channelData' ], function($item) use ($filter) {
                        return $item[ 'category_id' ] == $filter[ 'category_id' ];
                    });

                    return array_values(array_filter($data[ 'videoData' ], function($item) use ($channelIds) {
                        return isset($channelIds[ $item[ 'channel_id' ] ]);
                    }));
                }, 86400);
            } else if ($filter[ 'channel_id' ] > 0) {
                // если установлен канал - фильтруем данные
                if ($filter[ 'noCache' ])
                    Yii::$app->cache->delete($cacheId . '-ch=' . $filter[ 'channel_id' ]);

                $data[ 'videoData' ] = Yii::$app->cache->getOrSet($cacheId . '-ch=' . $filter[ 'channel_id' ], function() use ($data, $filter) {
                    return array_values(array_filter($data[ 'videoData' ], function($item) use ($filter) {
                        return $item[ 'channel_id' ] == $filter[ 'channel_id' ];
                    }));
                }, 86400);
            } else if (isset($filter[ 'query' ])) {
                // если включен поиск, то фильтруем данные
                if ($filter[ 'noCache' ])
                    Yii::$app->cache->delete($cacheId . '-q=' . $filter[ 'query' ]);

                $data[ 'videoData' ] = Yii::$app->cache->getOrSet($cacheId . '-q=' . $filter[ 'query' ], function() use ($data, $filter) {
                    $videoIds = array_fill_keys(Videos::searchByQuery($filter[ 'query' ]), 1);

                    return array_values(array_filter($data[ 'videoData' ], function($item) use ($videoIds) {
                        return isset($videoIds[ $item[ 'id' ] ]);
                    }));
                }, 86400);
            }

            Yii::endProfile('Фильтрация результатов по запросу');
        } else {
            // полные данные без фильтрации и без подстановки videoInfo
        }

        Yii::beginProfile('Подстановка данных по позициям при отсутствии');
        $adIds = array_map(function($item) {
            return $item[ 'id' ];
        }, array_filter($data[ 'videoData' ], function($item) use ($videoInfo) {
            return $item[ 'ad' ] == 1 && !isset($videoInfo[ $item[ 'id' ] ]);
        }));

        if (!empty($adIds)) {
            $adData = Yii::$app->db->createCommand("SELECT v.id, v.name, v.video_link, v.image_url FROM videos v WHERE v.id IN (" . implode(",", $adIds) . ")")->queryAll();
            $adData = array_combine(array_map(function($item) {
                return $item[ 'id' ];
            }, $adData), array_map(function($item) {
                unset($item[ 'id' ]);
                return $item;
            }, $adData));

            foreach ($adIds as $id => $videoId)
                $videoInfo[ $videoId ] = $adData[ $videoId ];
        }
        Yii::endProfile('Подстановка данных по позициям при отсутствии');

        Yii::beginProfile('Фильтрация несуществующих данных');
        $data[ 'videoData' ] = array_values(array_filter($data[ 'videoData' ], function($item) use ($videoInfo) {
            return isset($videoInfo[ $item[ 'id' ] ]);
        }));
        Yii::endProfile('Фильтрация несуществующих данных');

        $count = count($data[ 'videoData' ]);

        if (!$filter[ 'fullData' ]) {
            Yii::beginProfile('Усечение результатов');
            $data[ 'videoData' ] = array_slice($data[ 'videoData' ], ($page - 1) * Statistics::PAGINATION_ROW_COUNT, Statistics::PAGINATION_ROW_COUNT);
            Yii::endProfile('Усечение результатов');

            Yii::beginProfile('Подстановка связанных данных');

            foreach ($data[ 'videoData' ] as $id => $value) {
                if (isset($videoInfo[ $value[ 'id' ] ]))
                    $data[ 'videoData' ][ $id ] = array_merge($value, $videoInfo[ $value[ 'id' ] ]);
                else
                    unset($data[ 'videoData' ][ $id ]);

                $data[ 'videoData' ][ $id ][ 'channel' ] = $data[ 'channelData' ][ $value[ 'channel_id' ] ];
            }

            Yii::endProfile('Подстановка связанных данных');
        }

        $time = microtime(true) - $time;

        return [
            'data' => is_array($data[ 'videoData' ]) ? $data[ 'videoData' ] : [],
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
                'sql' => self::formatSql($videoSql) . "\n\n" .
                    self::formatSql($channelSql) . "\n\n" .
                    self::formatSql($lastTimeSql) . "\n\n" .
                    self::formatSql($prevTimeSql) . "\n\n" .
                    self::formatSql($prevTimeSql2) . "\n\n" .
                    self::formatSql($positionsSql),
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
