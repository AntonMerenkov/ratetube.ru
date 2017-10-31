<?php

namespace app\commands;

use app\components\HighloadAPI;
use app\components\YoutubeAPI;
use app\models\ApiKeys;
use app\models\ApiKeyStatistics;
use app\models\Categories;
use app\models\Channels;
use app\models\Positions;
use app\models\Profiling;
use app\components\Statistics;
use app\models\StatisticsMinute;
use app\models\Tags;
use app\models\Videos;
use app\widgets\PopularTags;
use app\widgets\Streaming;
use app\widgets\TopChannels;
use DateInterval;
use DateTime;
use yii\console\Controller;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Агент для анализа видео на YouTube.
 */
class AgentController extends Controller
{
    /**
     * @var float
     */
    protected $time;
    /**
     * @var Profiling|null
     */
    protected $profiling;

    /**
     * Действия при инициализации контроллера.
     */
    public function init()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
    }

    /**
     * Инициализация профайлинга.
     *
     * @param \yii\base\Action $action
     * @return bool
     */
    public function beforeAction($action)
    {
        $this->time = microtime(true);

        $this->profiling = new Profiling();
        $this->profiling->code = 'agent-' . $action->id;
        $this->profiling->datetime = date('d.m.Y H:i:s', round($this->time / 10) * 10);

        return true;
    }

    /**
     * Сохранение данных профайлинга.
     *
     * @param \yii\base\Action $action
     * @param mixed $result
     * @return bool
     */
    public function afterAction($action, $result)
    {
        $this->profiling->duration = round(microtime(true) - $this->time, 2);
        $this->profiling->memory = memory_get_usage() / 1024 / 1024;
        $this->profiling->save();

        return true;
    }

    /**
     * Обновление списка видео.
     *
     * @throws \Exception
     */
    public function actionUpdateVideos()
    {
        $channelModels = Channels::find()->all();

        $channelsIds = ArrayHelper::map($channelModels, 'id', 'channel_link');
        $loadLastDays = ArrayHelper::map($channelModels, 'id', 'load_last_days');
        $loadLastDaysCat = ArrayHelper::map(Categories::find()->all(), 'id', 'load_last_days');

        foreach ($channelModels as $channelModel)
            if (isset($loadLastDaysCat[ $channelModel->category_id ]) && $loadLastDaysCat[ $channelModel->category_id ] > 0)
                $loadLastDays[ $channelModel->id ] = $loadLastDaysCat[ $channelModel->category_id ];

        $videoModels = Videos::find()->all();

        $videoModels = ArrayHelper::map($videoModels, 'id', function($item) {
            return $item;
        });

        $oldVideos = ArrayHelper::map($videoModels, 'id', 'video_link');

        // пытаемся загрузить данные из кэша
        $cachedData = [];
        $addedVideos = [];
        $cachedDir = false;
        if (file_exists(Yii::getAlias('@runtime/highload_cache/' . $this->action->id))) {
            $directories = glob(Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/*'));

            if (!empty($directories)) {
                $cachedDir = $directories[ 0 ];
                foreach (glob($cachedDir . '/*') as $file)
                    $cachedData[ basename($file) ] = unserialize(file_get_contents($file));
            }

            if (isset($cachedData[ 'added' ])) {
                $addedVideos = $cachedData[ 'added' ];
                unset($cachedData[ 'added' ]);
            }
        }

        if (empty($cachedData)) {
            // загрузку новых видео проводим только раз в час, в 15 минут
            /*if (date('i', strtotime($this->profiling->datetime)) != 15)
                return true;*/

            $cachedData = HighloadAPI::query('search', [
                'channelId' => $channelsIds,
                'type' => 'video',
                'order' => 'viewCount',
            ], [
                'snippet'
            ], YoutubeAPI::QUERY_PAGES);

            $cachedDir = Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/' . strtotime($this->profiling->datetime));

            if (!file_exists($cachedDir))
                mkdir($cachedDir, 0777, true);

            foreach ($cachedData as $id => $data) {
                file_put_contents($cachedDir . '/' . $id, serialize($data));
            }
        }

        ksort($cachedData, SORT_NUMERIC);

        $channelsLinks = array_flip($channelsIds);
        $oldVideosLinks = array_flip($oldVideos);
        $oldVideosActive = ArrayHelper::map($videoModels, 'id', 'active');

        $addedCount = 0;
        $unactiveCount = 0;
        foreach ($cachedData as $id => $result) {
            $result = unserialize(gzuncompress($result));
            if (!is_array($result))
                $result = [];

            $newVideoIds = [];
            foreach ($result as $item)
                $newVideoIds[ $item[ 'id' ][ 'videoId' ] ] = [
                    'id' => $item[ 'id' ][ 'videoId' ],
                    'title' => $item[ 'snippet' ][ 'title' ],
                    'date' => date('Y-m-d H:i:s', strtotime($item[ 'snippet' ][ 'publishedAt' ])),
                    'channel_id' => $item[ 'snippet' ][ 'channelId' ],
                    'image_url' => $item[ 'snippet' ][ 'thumbnails' ][ 'medium' ][ 'url' ]
                ];

            $transaction = Videos::getDb()->beginTransaction();

            try {
                $values = [];
                $unactiveIds = [];

                foreach ($newVideoIds as $videoData) {
                    // обновление картинок видео
                    if (isset($videoModels[ $oldVideosLinks[ $videoData[ 'id' ] ] ]) && $videoModels[ $oldVideosLinks[ $videoData[ 'id' ] ] ]->image_url == '' && $oldVideosActive[ $oldVideosLinks[ $videoData[ 'id' ] ] ]) {
                        $videoModels[ $oldVideosLinks[ $videoData[ 'id' ] ] ]->image_url = $videoData[ 'image_url' ];
                        $videoModels[ $oldVideosLinks[ $videoData[ 'id' ] ] ]->save();
                    }

                    $addedVideos[ $videoData[ 'id' ] ] = 1;

                    // установка статуса Неактивно
                    if (isset($videoModels[ $oldVideosLinks[ $videoData[ 'id' ] ] ])) {
                        $channelId = $channelsLinks[ $videoData[ 'channel_id' ] ];

                        if (($loadLastDays[ $channelId ] > 0) && (time() - strtotime($videoData[ 'date' ]) > $loadLastDays[ $channelId ] * 86400)) {
                            if ($oldVideosActive[ $oldVideosLinks[ $videoData[ 'id' ] ] ]) {
                                $unactiveIds[] = $oldVideosLinks[ $videoData[ 'id' ] ];
                                $unactiveCount++;
                            }
                        }
                    }

                    if (isset($oldVideosLinks[ $videoData[ 'id' ] ]))
                        continue;

                    $channelId = $channelsLinks[ $videoData[ 'channel_id' ] ];

                    if (($loadLastDays[ $channelId ] > 0) && (time() - strtotime($videoData[ 'date' ]) > $loadLastDays[ $channelId ] * 86400))
                        continue;

                    $values[] = [
                        'name' => mb_substr($videoData[ 'title' ], 0, 255),
                        'video_link' => $videoData[ 'id' ],
                        'channel_id' => $channelId,
                        'image_url' => $videoData[ 'image_url' ],
                    ];
                }

                if (!empty($values))
                    Yii::$app->db->createCommand()->batchInsert(Videos::tableName(), array_keys($values[ 0 ]), $values)->execute();

                if (!empty($unactiveIds))
                    Videos::updateAll([
                        'active' => 0
                    ], [
                        'id' => $unactiveIds,
                    ]);

                $transaction->commit();

                $addedCount += count($values);

                unset($cachedData[ $id ]);
                unlink($cachedDir . '/' . $id);

                file_put_contents($cachedDir . '/' . 'added', serialize($addedVideos));

                if (count(array_filter(glob($cachedDir . '/*'), function($item) {
                    return basename($item) != 'added';
                })) == 0) {
                    unlink($cachedDir . '/' . 'added');
                    rmdir($cachedDir);
                }

                echo str_pad("[" . (count($cachedData) + 1) .
                    "] Обработка данных, добавлено " . $addedCount .
                    ', помечено неактивными: ' . $unactiveCount .
                    ", память " . round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n", 80);
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        // установка неактивности для тех видео, которых нет в списке
        $activeVideosIds = array_values(array_map(function($item) {
            return $item[ 'id' ];
        }, array_filter(Videos::find()->active()->asArray()->all(), function($item) use ($addedVideos) {
            return !isset($addedVideos[ $item[ 'video_link' ] ]);
        })));

        if (!empty($activeVideosIds)) {
            Videos::updateAll([
                'active' => 0
            ], [
                'id' => $activeVideosIds
            ]);

            $unactiveCount += count($activeVideosIds);
        }

        Yii::info("Получено новых видео: " . $addedCount .
            ', помечено неактивными: ' . $unactiveCount .
            ', время: ' . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Обновление статистики по видео.
     * @param int $force
     * @return bool
     * @throws \Exception
     */
    public function actionUpdateStatistics($force = 0)
    {
        $videoIds = ArrayHelper::map(Videos::find()->active()->all(), 'id', 'video_link');

        // пытаемся загрузить данные из кэша
        $cachedData = [];
        $cachedDir = false;
        if (file_exists(Yii::getAlias('@runtime/highload_cache/' . $this->action->id))) {
            $directories = glob(Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/*'));

            if (!empty($directories)) {
                $cachedDir = $directories[ 0 ];
                foreach (glob($cachedDir . '/*') as $file)
                    $cachedData[ basename($file) ] = unserialize(file_get_contents($file));
            }
        }

        if (empty($cachedData)) {
            // загрузку новых видео проводим каждые 5 минут - каждый шаг crontab, доп проверка не нужна
            //if (date('i', strtotime($this->profiling->datetime)) % 5 != 0)
            //    return true;

            $cachedData = HighloadAPI::query('videos', [
                'id' => $videoIds
            ], [
                'statistics',
                'liveStreamingDetails'
            ], YoutubeAPI::QUERY_MULTIPLE);

            $cachedDir = Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/' . strtotime($this->profiling->datetime));

            if (!file_exists($cachedDir))
                mkdir($cachedDir, 0777, true);

            foreach ($cachedData as $id => $data) {
                file_put_contents($cachedDir . '/' . $id, serialize($data));
            }
        }

        ksort($cachedData, SORT_NUMERIC);

        $cachedTimestamp = end(explode('/', $cachedDir));
        $addedIntervals = [];
        $addedCount = 0;

        // находим время последней записи статистики в каждую таблицу
        $lastQueryTime = array_map(function ($item) {
            return 0;
        }, Statistics::$tableModels);

        foreach (array_keys($lastQueryTime) as $key) {
            $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];
            $lastQueryTime[ $key ] = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName() . ' WHERE datetime < "' .
                date('Y-m-d H:i:s', $cachedTimestamp) . '"')->queryScalar();
        }

        $videoIds = array_flip($videoIds);
        foreach ($cachedData as $id => $result) {
            $result = unserialize(gzuncompress($result));
            if (!is_array($result))
                $result = [];

            $videoStatistics = [];
            foreach ($result as $item) {
                $videoStatistics[ $item[ 'id' ] ] = $item[ 'statistics' ];

                if (isset($item[ 'liveStreamingDetails' ][ 'concurrentViewers' ]))
                    $videoStatistics[ $item[ 'id' ] ][ 'viewers' ] = $item[ 'liveStreamingDetails' ][ 'concurrentViewers' ];
            }

            $transaction = Yii::$app->db->beginTransaction();

            try {
                foreach (array_keys($lastQueryTime) as $key) {
                    if (!$force)
                        if (time() - strtotime($lastQueryTime[ $key ]) < Statistics::$appendInterval[ $key ])
                            continue;

                    $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];

                    $values = [];

                    foreach ($videoStatistics as $videoId => $videoData) {
                        if (!isset($videoIds[ $videoId ]))
                            continue;

                        $values[] = [
                            'datetime' => date('Y-m-d H:i:s', $cachedTimestamp),
                            'video_id' => $videoIds[ $videoId ],
                            'views' => $videoData[ 'viewCount' ],
                            'likes' => $videoData[ 'likeCount' ],
                            'dislikes' => $videoData[ 'dislikeCount' ],
                            'viewers' => $videoData[ 'viewers' ],
                        ];
                    }

                    if (!empty($values)) {
                        $addedIntervals[] = strtoupper(substr($key, 0, 1));
                        $addedCount += count($values);

                        Yii::$app->db->createCommand()->batchInsert($tableModel::tableName(), array_keys($values[ 0 ]), $values)->execute();
                    }
                }

                $transaction->commit();

                unset($cachedData[ $id ]);
                unlink($cachedDir . '/' . $id);

                if (count(glob($cachedDir . '/*')) == 0)
                    rmdir($cachedDir);

                echo str_pad("[" . (count($cachedData) + 1) .
                        "] Обработка данных, добавлено " . $addedCount . ", память " .
                        round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n", 80);
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }

        $addedIntervals = array_unique($addedIntervals);
        Yii::info("Получена статистика для " . $addedCount . " видео, интервалы: " .
            implode("", $addedIntervals) . ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Очистка старых значений статистики.
     */
    public function actionFlushStatistics()
    {
        $minQueryDate = array_map(function ($item) {
            return 0;
        }, Statistics::$tableModels);

        $statisticTables = [];
        foreach (array_keys($minQueryDate) as $key) {
            $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];
            $statisticTables[] = $tableModel::tableName();

            $lastDate = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName())->queryScalar();
            $minQueryDate[ $key ] = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName() . ' where datetime <= "' .
                date('Y-m-d H:i:s', strtotime($lastDate) - Statistics::$timeDiffs[ $key ]) . '"')->queryScalar();

            // двойной интервал (для колонки Изменение позиции)
            if (isset($minQueryDate[ $key ]))
                $minQueryDate[ $key ] = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName() . ' where datetime <= "' .
                    date('Y-m-d H:i:s', strtotime($minQueryDate[ $key ]) - Statistics::$timeDiffs[ $key ]) . '"')->queryScalar();
        }

        if (count(array_filter($minQueryDate, function ($item) {
                return !is_null($item);
            })) == 0)
            return true;

        // старый объем таблиц статистики
        $oldTableSize = array_sum(array_map(function ($item) {
            return $item[ 'DATA_LENGTH' ] + $item[ 'INDEX_LENGTH' ];
        }, array_filter(Statistics::getTableSizeData(), function ($item) use ($statisticTables) {
            return in_array($item[ 'TABLE_NAME' ], $statisticTables);
        })));

        //$transaction = Yii::$app->db->beginTransaction();

        // Очистка данных за пределами двойного временного интервала
        foreach ($minQueryDate as $key => $date) {
            if (is_null($date))
                continue;

            $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];

            $count = Yii::$app->db->createCommand('select count(*) from ' . $tableModel::tableName() . ' where datetime < "' . $date . '"')->queryScalar();
            if ($count > 0)
                for ($i = 1; $i <= ceil($count / 10000); $i++) {
                    Yii::$app->db->createCommand('delete from ' . $tableModel::tableName() . ' where datetime < "' . $date . '" LIMIT 10000')->execute();
                    echo "Удаление двойного интервала из "  . $tableModel::tableName() . ", " . $i . "/" . ceil($count / 10000) . "\n";
                }
        }

        // Очистка данных для неактивных видео
        $activeVideosIds = ArrayHelper::map(Videos::find()->where(['active' => 1])->asArray()->all(), 'id', 'id');

        if (!empty($activeVideosIds))
            foreach ($minQueryDate as $key => $date) {
                $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];

                $count = Yii::$app->db->createCommand('select count(*) from ' . $tableModel::tableName() . ' where video_id NOT IN (' . implode(',', $activeVideosIds) . ')')->queryScalar();
                if ($count > 0)
                    for ($i = 1; $i <= ceil($count / 10000); $i++) {
                        Yii::$app->db->createCommand('delete from ' . $tableModel::tableName() .' where video_id NOT IN (' . implode(',', $activeVideosIds) . ') LIMIT 10000')->execute();
                        echo "Удаление неактивных видео из "  . $tableModel::tableName() . ", " . $i . "/" . ceil($count / 10000) . "\n";
                    }
            }

        foreach ($minQueryDate as $key => $date) {
            $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];

            Yii::$app->db->createCommand('OPTIMIZE TABLE ' . $tableModel::tableName())->execute();
            echo "Оптимизация таблицы "  . $tableModel::tableName() . "\n";
        }

        // новый объем таблиц статистики
        $newTableSize = array_sum(array_map(function ($item) {
            return $item[ 'DATA_LENGTH' ] + $item[ 'INDEX_LENGTH' ];
        }, array_filter(Statistics::getTableSizeData(), function ($item) use ($statisticTables) {
            return in_array($item[ 'TABLE_NAME' ], $statisticTables);
        })));

        //$transaction->commit();

        Yii::info("Таблицы статистики очищены, " . Yii::$app->formatter->asShortSize($oldTableSize - $newTableSize, 1) . " удалено, время: " .
            Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Очистка неактуальных видео.
     */
    public function actionFlushVideos()
    {
        // загружаем критерии удаления для всех каналов
        $channelCriteria = ArrayHelper::map(Channels::find()->with('category')->all(), 'id', function ($item) {
            return $item->flush_timeframe != '' ? [
                'flush_timeframe' => $item->flush_timeframe,
                'flush_count' => $item->flush_count,
            ] : [
                'flush_timeframe' => $item->category->flush_timeframe,
                'flush_count' => $item->category->flush_count,
            ];
        });

        $framesData = array_unique(array_filter(array_map(function ($item) {
            return $item[ 'flush_timeframe' ];
        }, $channelCriteria), function ($item) {
            return $item != '';
        }));

        if (empty($framesData))
            return true;

        $framesData = array_map(function ($item) {
            return Statistics::getStatistics(1, [
                'timeType' => $item,
                'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF,
                'fullData' => true
            ]);
        }, array_combine($framesData, $framesData));

        $videoIds = [];

        foreach ($framesData as $key => $frameData) {
            // пропустить обработку, если не набрана статистика
            if ($frameData[ 'time' ][ 'from' ] == $frameData[ 'time' ][ 'to' ])
                continue;

            foreach ($frameData[ 'data' ] as $videoData) {
                if ($channelCriteria[ $videoData[ 'channel' ][ 'id' ] ][ 'flush_timeframe' ] != $key)
                    continue;

                // если статистики по данному видео нет - не удалять его
                if ($videoData[ 'views_diff' ] < $channelCriteria[ $videoData[ 'channel' ][ 'id' ] ][ 'flush_count' ] && $videoData[ 'views' ] > 0 && !is_null($videoData[ 'views_old' ])) {
                    $videoIds[] = $videoData[ 'id' ];
                }
            }
        }

        // рекламные видео, не привязанные к позиции
        $adVideos = ArrayHelper::map(Videos::find()->where('channel_id IS NULL AND active = 1')->all(), 'id', 'id');
        $adPositions = ArrayHelper::map(Positions::find()->all(), 'id', 'video_id');
        $unusedVideos = array_diff($adVideos, $adPositions);

        foreach ($unusedVideos as $id)
            $videoIds[] = $id;

        if (empty($videoIds))
            return true;

        foreach ($framesData as $frameData)
            Yii::$app->cache->delete($frameData[ 'db' ][ 'cache_id' ]);

        Videos::updateAll(['active' => 0], 'id IN (' . implode(',', $videoIds) . ')');

        Yii::info(Yii::t('app', '{n, plural, one{# видео помечено как неактуальное} other{# видео помечены как неактуальные}}', ['n' => count($videoIds)]) . ", время: " .
            Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Обновление статистики по количеству подписчиков.
     */
    public function actionUpdateSubscribers()
    {
        $channelIds = ArrayHelper::map(Channels::find()->all(), 'id', 'channel_link');

        // пытаемся загрузить данные из кэша
        $cachedData = [];
        $cachedDir = false;
        if (file_exists(Yii::getAlias('@runtime/highload_cache/' . $this->action->id))) {
            $directories = glob(Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/*'));

            if (!empty($directories)) {
                $cachedDir = $directories[ 0 ];
                foreach (glob($cachedDir . '/*') as $file)
                    $cachedData[ basename($file) ] = unserialize(file_get_contents($file));
            }
        }

        if (empty($cachedData)) {
            // загрузку новых видео проводим каждые 5 минут - каждый шаг crontab, доп проверка не нужна
            //if (date('i', strtotime($this->profiling->datetime)) % 5 != 0)
            //    return true;

            $cachedData = HighloadAPI::query('channels', [
                'id' => $channelIds
            ], [
                'statistics'
            ], YoutubeAPI::QUERY_MULTIPLE);

            $cachedDir = Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/' . strtotime($this->profiling->datetime));

            if (!file_exists($cachedDir))
                mkdir($cachedDir, 0777, true);

            foreach ($cachedData as $id => $data) {
                file_put_contents($cachedDir . '/' . $id, serialize($data));
            }
        }

        ksort($cachedData, SORT_NUMERIC);

        $addedChannels = 0;
        foreach ($cachedData as $id => $result) {
            $result = unserialize(gzuncompress($result));
            if (!is_array($result))
                $result = [];

            $subscribersData = [];
            foreach ($result as $item)
                $subscribersData[ $item[ 'id' ] ] = $item[ 'statistics' ];

            $transaction = Yii::$app->db->beginTransaction();

            foreach ($channelIds as $channelId => $channelLink) {
                Channels::updateAll([
                    'subscribers_count' => $subscribersData[ $channelLink ][ 'subscriberCount' ]
                ], [
                    'id' => $channelId
                ]);
            }

            $addedChannels += count($channelIds);

            $transaction->commit();

            unset($cachedData[ $id ]);
            unlink($cachedDir . '/' . $id);

            if (count(glob($cachedDir . '/*')) == 0)
                rmdir($cachedDir);

            echo str_pad("[" . (count($cachedData) + 1) .
                "] Обработка данных, добавлено " . $addedChannels . ", память " .
                round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n", 80);
        }

        Yii::info("Количество подписчиков обновлено для " . Yii::t('app', '{n, plural, one{# канала} other{# каналов}}', ['n' => $addedChannels]) .
            ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Разбиение UTF-8 строки.
     * @param $str
     * @return array
     */
    private function utf8StrSplit($str)
    {
        $split = 1;
        $array = array();
        for ($i = 0; $i < strlen($str);) {
            $value = ord($str[ $i ]);
            if ($value > 127) {
                if ($value >= 192 && $value <= 223)
                    $split = 2;
                elseif ($value >= 224 && $value <= 239)
                    $split = 3;
                elseif ($value >= 240 && $value <= 247)
                    $split = 4;
            } else {
                $split = 1;
            }
            $key = NULL;
            for ($j = 0; $j < $split; $j++, $i++) {
                $key .= $str[ $i ];
            }
            array_push($array, $key);
        }
        return $array;
    }

    /**
     * Удаление лишних символов из строки Unicode.
     * @param string $str
     * @return string
     */
    private function clearString($str)
    {
        $sru = 'ёйцукенгшщзхъфывапролджэячсмитьбю';
        $s1 = array_merge($this->utf8StrSplit($sru), $this->utf8StrSplit(strtoupper($sru)), range('A', 'Z'), range('a', 'z'), range('0', '9'), array('&', ' ', '#', ';', '%', '?', ':', '(', ')', '-', '_', '=', '+', '[', ']', ',', '.', '/', '\\'));
        $codes = array();
        for ($i = 0; $i < count($s1); $i++) {
            $codes[] = ord($s1[ $i ]);
        }
        $str_s = $this->utf8StrSplit($str);
        for ($i = 0; $i < count($str_s); $i++) {
            if (!in_array(ord($str_s[ $i ]), $codes)) {
                $str = str_replace($str_s[ $i ], '', $str);
            }
        }
        return $str;
    }

    /**
     * Удаление лишних символов из Unicode-строки.
     *
     * @param $string string|array
     * @return string
     */
    private function processUnicode($string)
    {
        if (is_array($string))
            return array_map(function ($item) {
                return $this->clearString($item);
            }, $string);
        else
            return $this->clearString($string);
    }

    /**
     * Агент для обновления тэгов поиска.
     */
    public function actionUpdateTags()
    {
        // загружаем текущие данные из БД
        $videoIds = ArrayHelper::map(Yii::$app->db->createCommand('SELECT id, video_link FROM ' . Videos::tableName())->queryAll(), 'id', 'video_link');

        // находим ID видео, у которых нет тэгов
        $existingIds = Yii::$app->db->createCommand('SELECT DISTINCT (video_id) FROM ' . Tags::tableName())->queryColumn();
        $existingIds = array_fill_keys($existingIds, true);

        $videoIds = array_diff_key($videoIds, $existingIds);

        if (empty($videoIds))
            return true;

        // пытаемся загрузить данные из кэша
        $cachedData = [];
        $cachedDir = false;
        if (file_exists(Yii::getAlias('@runtime/highload_cache/' . $this->action->id))) {
            $directories = glob(Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/*'));

            if (!empty($directories)) {
                $cachedDir = $directories[ 0 ];
                foreach (glob($cachedDir . '/*') as $file)
                    $cachedData[ basename($file) ] = unserialize(file_get_contents($file));
            }
        }

        if (empty($cachedData)) {
            // загрузку новых видео проводим каждые 5 минут - каждый шаг crontab, доп проверка не нужна
            //if (date('i', strtotime($this->profiling->datetime)) % 5 != 0)
            //    return true;

            $cachedData = HighloadAPI::query('videos', [
                'id' => $videoIds
            ], [
                'snippet'
            ], YoutubeAPI::QUERY_MULTIPLE);

            $cachedDir = Yii::getAlias('@runtime/highload_cache/' . $this->action->id . '/' . strtotime($this->profiling->datetime));

            if (!file_exists($cachedDir))
                mkdir($cachedDir, 0777, true);

            foreach ($cachedData as $id => $data) {
                file_put_contents($cachedDir . '/' . $id, serialize($data));
            }
        }

        ksort($cachedData, SORT_NUMERIC);

        $videoIds = array_flip($videoIds);

        //echo "Начинаем обработку [" . round(microtime(true) - $this->time, 2) . "]\n";

        $addedTags = 0;
        foreach ($cachedData as $id => $result) {
            $result = unserialize(gzuncompress($result));
            if (!is_array($result))
                $result = [];

            //echo "Данные готовы [" . round(microtime(true) - $this->time, 2) . "]\n";

            $newTags = [];
            foreach ($result as $item)
                $newTags[ $videoIds[ $item[ 'id' ] ] ] = [
                    Tags::TYPE_TAG => is_array($item[ 'snippet' ][ 'tags' ]) ? array_slice($this->processUnicode($item[ 'snippet' ][ 'tags' ]), 0, 3) : null,
                    Tags::TYPE_CHANNEL => [
                        $this->processUnicode($item[ 'snippet' ][ 'channelTitle' ])
                    ],
                    Tags::TYPE_TITLE => [
                        $this->processUnicode($item[ 'snippet' ][ 'title' ])
                    ],
                ];

            //echo "Данные отсортированы [" . round(microtime(true) - $this->time, 2) . "]\n";

            $transaction = Yii::$app->db->beginTransaction();

            // проанализировать тэги, добавить отсутствующие и удалить ненужные
            $addData = [];
            foreach ($videoIds as $videoLink => $videoId) {
                foreach (Tags::$weights as $type => $weight) {
                    if (!isset($newTags[ $videoId ][ $type ]))
                        continue;

                    foreach ($newTags[ $videoId ][ $type ] as $value)
                        $addData[] = [
                            'video_id' => $videoId,
                            'type' => $type,
                            'text' => $value,
                        ];
                }
            }

            //echo "Данные разбиты на части [" . round(microtime(true) - $this->time, 2) . "]\n";

            if (!empty($addData))
                Yii::$app->db->createCommand()->batchInsert(Tags::tableName(), array_keys($addData[ 0 ]), $addData)->execute();

            $addedTags += count($addData);

            $transaction->commit();

            unset($cachedData[ $id ]);
            unlink($cachedDir . '/' . $id);

            if (count(glob($cachedDir . '/*')) == 0)
                rmdir($cachedDir);

            //echo "Данные добавлены в БД [" . round(microtime(true) - $this->time, 2) . "]\n";

            echo str_pad("[" . (count($cachedData) + 1) .
                "] Обработка данных, добавлено " . $addedTags . ", память " .
                round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n", 80);
        }

        Yii::info("Тэги обновлены, добавлено " . Yii::t('app', '{n, plural, one{# тэг} other{# тэгов}}', ['n' => $addedTags]) .
            ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Обновление квот.
     */
    public function actionUpdateQuota()
    {
        $apiKeys = array_filter(ApiKeys::find()->all(), function($item) {
            return !is_null($item->lastStatistics) && $item->lastStatistics->quota < YoutubeAPI::MAX_QUOTA_VALUE;
        });

        $ids = [];
        foreach ($apiKeys as $id => $apiKey) {
            $data = ApiKeys::validateKey($apiKey->key);

            if ($data[ 'status' ] == false && isset($data[ 'errorReason' ]) && $data[ 'errorReason' ] == 'dailyLimitExceeded') {
                $model = $apiKey->lastStatistics;

                $currentDate = new DateTime();
                if ($currentDate < new DateTime(date('d.m.Y') . ' ' . YoutubeAPI::QUOTA_REFRESH_TIME))
                    $currentDate->sub(new DateInterval('P1D'));

                if (!is_null($model)) {
                    $statDate = new DateTime($model->date);

                    if ($statDate->format('d.m.Y') != $currentDate->format('d.m.Y'))
                        $model = null;
                }

                if (is_null($model)) {
                    $model = new ApiKeyStatistics();
                    $model->api_key_id = $apiKey->id;
                    $model->quota = 0;
                    $model->date = $currentDate->format('Y-m-d');
                    $model->save();
                }

                $ids[] = $model->id;
            }
        }

        if (!empty($ids)) {
            ApiKeyStatistics::updateAll(['quota' => YoutubeAPI::MAX_QUOTA_VALUE], ['id' => $ids]);

            Yii::error("Квота исчерпана для " . Yii::t('app', '{n, plural, one{# ключа} other{# ключей}}', ['n' => count($ids)]) .
                ", " . Yii::t('app', '{n, plural, one{остался # ключ} few{осталось # ключа} other{осталось # ключей}}', ['n' => count($apiKeys) - count($ids)]) .
                ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
                " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
        }
    }

    /**
     * Построение кэша статистики.
     */
    public function actionGenerateCache()
    {
        /**
         * Генерация кэша для статистики
         */
        $cacheHistory = Yii::$app->cache->get(Statistics::CACHE_HISTORY_KEY);
        if ($cacheHistory === false)
            $cacheHistory = [];

        $addedIntervals = [];
        foreach (Statistics::$timeTypes as $type => $name) {
            echo "Обновляем статистику для интервала " . $name . "\n";

            $statistics = Statistics::getStatistics(1, [
                'timeType' => $type,
                'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF
            ]);

            if (!isset($cacheHistory[ $type ]) || !in_array($statistics[ 'db' ][ 'cache_id' ], $cacheHistory[ $type ])) {
                echo $statistics[ 'db' ][ 'cache_id' ] . "\n";
                $addedIntervals[] = substr(str_replace('Statistics', '', Statistics::$tableModels[ $type ]), 0, 1);
            }

            echo "Время: " . round(microtime(true) - $this->time, 2) . " сек\n";
            echo "Память: " . round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n";
            unset($statistics);
        }

        /**
         * Генерация кэша для виджетов
         */
        $result = TopChannels::updateCache();
        if ($result)
            echo "Статистика для виджета TopChannels обновлена, время: " .
                round(microtime(true) - $this->time, 2) . " сек, память: " .
                round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n";

        $result = Streaming::updateCache();
        if ($result)
            echo "Статистика для виджета Streaming обновлена, время: " .
                round(microtime(true) - $this->time, 2) . " сек, память: " .
                round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n";

        $result = PopularTags::updateCache();
        if ($result)
            echo "Статистика для виджета PopularTags обновлена, время: " .
                round(microtime(true) - $this->time, 2) . " сек, память: " .
                round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n";

        if (!empty($addedIntervals))
            Yii::info("Сгенерирован кэш для интервалов и виджетов " .
                implode("", $addedIntervals) . ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
                " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
        else
            Yii::info("Кэш сгенерирован и существует, время: " . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
                " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Очистка устаревшего кеша статистики.
     */
    public function actionFlushCache()
    {
        $cacheHistory = Yii::$app->cache->get(Statistics::CACHE_HISTORY_KEY);
        if ($cacheHistory === false)
            $cacheHistory = [];

        $elementCount = 0;
        foreach ($cacheHistory as $type => $values) {
            $cacheExists = false;

            foreach ($values as $id => $item) {
                if (!$cacheExists) {
                    if (Yii::$app->cache->exists($item))
                        $cacheExists = true;
                    else
                        unset($cacheHistory[ $type ][ $id ]);
                } else {
                    $elementCount++;
                    Yii::$app->cache->delete($item);
                    unset($cacheHistory[ $type ][ $id ]);
                }
            }

            $cacheHistory[ $type ] = array_values($cacheHistory[ $type ]);
        }

        Yii::$app->cache->set(Statistics::CACHE_HISTORY_KEY, $cacheHistory);

        Yii::info("Очищен кэш, удалено элементов: " .
            $elementCount . ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $this->time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Тестирование HighloadAPI.
     */
    public function actionTestHighload()
    {
        $videoIds = ArrayHelper::map(Videos::find()->active()->all(), 'id', 'video_link');
        $channelsIds = ArrayHelper::map(Channels::find()->all(), 'id', 'channel_link');

        // Запрос на получение статистики
        /*echo "==== Запрос на получение статистики ====\n";

        echo "Обычный\n";
        $time = microtime(true);
        $response = YoutubeAPI::query('videos', ['id' => $videoIds], ['statistics', 'liveStreamingDetails'], YoutubeAPI::QUERY_MULTIPLE);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "Highload\n";
        $time = microtime(true);
        $response = HighloadAPI::query('videos', ['id' => $videoIds], ['statistics', 'liveStreamingDetails'], YoutubeAPI::QUERY_MULTIPLE);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "\n";*/

        /*// Одиночный запрос
        echo "==== Одиночный запрос ====\n";

        echo "Обычный\n";
        $time = microtime(true);
        $response = YoutubeAPI::query('channels', ['forUsername' => 'starmedia'], ['snippet', 'statistics']);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "Highload\n";
        $time = microtime(true);
        $response = HighloadAPI::query('channels', ['forUsername' => 'starmedia'], ['snippet', 'statistics']);
        echo round(microtime(true) - $time, 2) . " сек.\n";*/

        /*echo "\n";

        // Множественный запрос
        echo "==== Множественный запрос ====\n";

        echo "Обычный\n";
        $time = microtime(true);
        $response = YoutubeAPI::query('videos', ['id' => $videoIds], ['snippet'], YoutubeAPI::QUERY_MULTIPLE);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "Highload\n";
        $time = microtime(true);
        $response = HighloadAPI::query('videos', ['id' => $videoIds], ['snippet'], YoutubeAPI::QUERY_MULTIPLE);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "\n";*/

        // Постраничный запрос
        /*echo "==== Постраничный запрос ====\n";

        echo "Обычный\n";
        $time = microtime(true);
        $response = YoutubeAPI::query('search', [
            'channelId' => $channelsIds,
            'type' => 'video',
            'order' => 'viewCount',
        ], [
            'snippet'
        ], YoutubeAPI::QUERY_PAGES);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "Highload\n";
        $time = microtime(true);
        $response = HighloadAPI::query('search', [
            'channelId' => $channelsIds,
            'type' => 'video',
            'order' => 'viewCount',
        ], [
            'snippet'
        ], YoutubeAPI::QUERY_PAGES);
        echo round(microtime(true) - $time, 2) . " сек.\n";*/
    }

    public function actionTestStatistics()
    {
        $statistics = Statistics::getStatistics(1, [
            //'category_id' => 3,
            'timeType' => Statistics::QUERY_TIME_HOUR,
            'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF,
            //'findCached' => true,
            'noCache' => true,
        ]);

        echo "--- Время подробно ---\n";
        foreach (Yii::getLogger()->getProfiling() as $item)
            echo "[" . round($item[ 'duration' ], 2) . "] " . $item[ 'info' ] . "\n";

        echo "Элементов: " . $statistics[ 'pagination' ][ 'count' ] . "\n";
        echo "Объем данных: " . round(strlen(serialize($statistics)) / 1024 / 1024, 2) . " МБ\n";
        echo round(memory_get_peak_usage() / 1024 / 1024, 2) . " МБ\n";
        echo round(microtime(true) - $this->time, 2) . " сек.\n";
    }
}
