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
     * Действия при инициализации контроллера.
     */
    public function init()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
    }

    /**
     * Обновление списка видео.
     *
     * @param null $channel_id
     * @throws \Exception
     */
    public function actionUpdateVideos($channel_id = null)
    {
        $time = microtime(true);

        $profiling = new Profiling();
        $profiling->code = 'agent-update-videos';
        $profiling->datetime = date('d.m.Y H:i:s', round($time / 10) * 10);

        if (!is_null($channel_id))
            $channelModels = Channels::find()->where(['id' => $channel_id])->all();
        else
            $channelModels = Channels::find()->all();

        $channelsIds = ArrayHelper::map($channelModels, 'id', 'channel_link');
        $loadLastDays = ArrayHelper::map($channelModels, 'id', 'load_last_days');

        if (!is_null($channel_id))
            $videoModels = Videos::find()->where(['channel_id' => $channel_id])->all();
        else
            $videoModels = Videos::find()->all();

        $videoModels = ArrayHelper::map($videoModels, 'id', function($item) {
            return $item;
        });

        $oldVideos = ArrayHelper::map($videoModels, 'id', 'video_link');
        $newVideoIds = Videos::getByChannelIds($channelsIds);

        $transaction = Videos::getDb()->beginTransaction();

        try {
            $values = [];

            foreach ($newVideoIds as $videoData) {
                // обновление картинок видео
                if (isset($videoModels[ array_search($videoData[ 'id' ], $oldVideos) ]) && $videoModels[ array_search($videoData[ 'id' ], $oldVideos) ]->image_url == '') {
                    $videoModels[ array_search($videoData[ 'id' ], $oldVideos) ]->image_url = $videoData[ 'image_url' ];
                    $videoModels[ array_search($videoData[ 'id' ], $oldVideos) ]->save();
                }

                if (in_array($videoData[ 'id' ], $oldVideos))
                    continue;

                $channelId = array_search($videoData[ 'channel_id' ], $channelsIds);

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

            $profiling->duration = round(microtime(true) - $time, 2);
            $profiling->memory = memory_get_usage() / 1024 / 1024;
            $profiling->save();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        Yii::info("Получено новых видео: " . count(array_diff(array_map(function ($item) {
                return $item[ 'id' ];
            }, $newVideoIds), $oldVideos)) .
            ', время: ' . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) .
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
        $time = microtime(true);

        $profiling = new Profiling();
        $profiling->code = 'agent-update-statistics';
        $profiling->datetime = date('d.m.Y H:i:s', round($time / 10) * 10);

        $videoIds = ArrayHelper::map(Videos::find()->active()->all(), 'id', 'video_link');
        $videoStatistics = Statistics::getByVideoIds($videoIds);

        if (isset($videoStatistics[ 'error' ])) {
            Yii::warning($videoStatistics[ 'error' ], 'agent');
            return true;
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // находим время последней записи статистики в каждую таблицу
            $lastQueryTime = array_map(function ($item) {
                return 0;
            }, Statistics::$tableModels);

            foreach (array_keys($lastQueryTime) as $key) {
                $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];
                $lastQueryTime[ $key ] = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName())->queryScalar();
            }

            $addedIntervals = [];
            foreach (array_keys($lastQueryTime) as $key) {
                if (!$force)
                    if (time() - strtotime($lastQueryTime[ $key ]) < Statistics::$appendInterval[ $key ])
                        continue;

                $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];

                $values = [];

                foreach ($videoStatistics as $videoId => $videoData) {
                    if (!in_array($videoId, $videoIds))
                        continue;

                    $values[] = [
                        'datetime' => date('Y-m-d H:i:s', strtotime($profiling->datetime)),
                        'video_id' => array_search($videoId, $videoIds),
                        'views' => $videoData[ 'viewCount' ],
                        'likes' => $videoData[ 'likeCount' ],
                        'dislikes' => $videoData[ 'dislikeCount' ],
                        'viewers' => $videoData[ 'viewers' ],
                    ];
                }

                if (!empty($values)) {
                    $addedIntervals[] = strtoupper(substr($key, 0, 1));

                    Yii::$app->db->createCommand()->batchInsert($tableModel::tableName(), array_keys($values[ 0 ]), $values)->execute();
                }
            }

            // обновляем кешированную статистику
            foreach (Statistics::$timeTypes as $type => $name) {
                $statistics = Statistics::getStatistics(1, [
                    'timeType' => $type,
                    'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF
                ]);

                unset($statistics);
            }

            $profiling->duration = round(microtime(true) - $time, 2);
            $profiling->memory = memory_get_usage() / 1024 / 1024;
            $profiling->save();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        Yii::info("Получена статистика для " . count($videoIds) . " видео, интервалы: " .
            implode("", $addedIntervals) . ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Очистка старых значений статистики.
     */
    public function actionFlushStatistics()
    {
        $time = microtime(true);

        $profiling = new Profiling();
        $profiling->code = 'agent-flush-statistics';
        $profiling->datetime = date('d.m.Y H:i:s', round($time / 10) * 10);

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

        $oldTableSize = array_sum(array_map(function ($item) {
            return $item[ 'DATA_LENGTH' ] + $item[ 'INDEX_LENGTH' ];
        }, array_filter(Statistics::getTableSizeData(), function ($item) use ($statisticTables) {
            return in_array($item[ 'TABLE_NAME' ], $statisticTables);
        })));

        $transaction = Yii::$app->db->beginTransaction();

        foreach ($minQueryDate as $key => $date) {
            if (is_null($date))
                continue;

            $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];
            Yii::$app->db->createCommand('delete from ' . $tableModel::tableName() . ' where datetime < "' . $date . '"')->execute();
        }

        $newTableSize = array_sum(array_map(function ($item) {
            return $item[ 'DATA_LENGTH' ] + $item[ 'INDEX_LENGTH' ];
        }, array_filter(Statistics::getTableSizeData(), function ($item) use ($statisticTables) {
            return in_array($item[ 'TABLE_NAME' ], $statisticTables);
        })));

        $profiling->duration = round(microtime(true) - $time, 2);
        $profiling->memory = memory_get_usage() / 1024 / 1024;
        $profiling->save();

        $transaction->commit();

        Yii::info("Таблицы статистики очищены, " . Yii::$app->formatter->asShortSize($oldTableSize - $newTableSize, 1) . " удалено, время: " .
            Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Очистка неактуальных видео.
     */
    public function actionFlushVideos()
    {
        $time = microtime(true);

        $profiling = new Profiling();
        $profiling->code = 'agent-flush-videos';
        $profiling->datetime = date('d.m.Y H:i:s', round($time / 10) * 10);

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

        $profiling->duration = round(microtime(true) - $time, 2);
        $profiling->memory = memory_get_usage() / 1024 / 1024;
        $profiling->save();

        Yii::info(Yii::t('app', '{n, plural, one{# видео помечено как неактуальное} other{# видео помечены как неактуальные}}', ['n' => count($videoIds)]) . ", время: " .
            Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Обновление статистики по количеству подписчиков.
     */
    public function actionUpdateSubscribers()
    {
        $time = microtime(true);

        $profiling = new Profiling();
        $profiling->code = 'agent-update-subscribers';
        $profiling->datetime = date('d.m.Y H:i:s', round($time / 10) * 10);

        $channelIds = ArrayHelper::map(Channels::find()->all(), 'id', 'channel_link');

        $response = HighloadAPI::query('channels', ['id' => $channelIds], ['statistics'], YoutubeAPI::QUERY_MULTIPLE);

        if ($response == false)
            return false;

        $result = [];
        foreach ($response as $item)
            $result[ $item[ 'id' ] ] = $item[ 'statistics' ];

        $transaction = Yii::$app->db->beginTransaction();

        foreach ($channelIds as $id => $channelId) {
            Channels::updateAll([
                'subscribers_count' => (int)$result[ $channelId ][ 'subscriberCount' ]
            ], [
                'id' => $id
            ]);
        }

        $profiling->duration = round(microtime(true) - $time, 2);
        $profiling->memory = memory_get_usage() / 1024 / 1024;
        $profiling->save();

        $transaction->commit();

        Yii::info("Количество подписчиков обновлено для " . Yii::t('app', '{n, plural, one{# канала} other{# каналов}}', ['n' => count($result)]) .
            ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) .
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
        $time = microtime(true);

        $profiling = new Profiling();
        $profiling->code = 'agent-update-tags';
        $profiling->datetime = date('d.m.Y H:i:s', round($time / 10) * 10);

        // загружаем текущие данные из БД
        $videos = ArrayHelper::map(Videos::find()->all(), 'id', function ($item) {
            return $item;
        });
        $tags = ArrayHelper::map(Tags::find()->all(), 'id', function ($item) {
            return $item;
        });

        $videoIds = ArrayHelper::map($videos, 'id', 'video_link');

        // загрузить все тэги добавленных видео множественным запросом
        $newTags = [];
        $urlArray = [];

        // делаем запрос на получение статистики по каналам
        $response = HighloadAPI::query('videos', ['id' => $videoIds], ['snippet'], YoutubeAPI::QUERY_MULTIPLE);

        if ($response == false)
            return false;

        foreach ($response as $item)
            $newTags[ array_search($item[ 'id' ], $videoIds) ] = [
                Tags::TYPE_TAG => $this->processUnicode($item[ 'snippet' ][ 'tags' ]),
                Tags::TYPE_CHANNEL => [
                    $this->processUnicode($item[ 'snippet' ][ 'channelTitle' ])
                ],
                Tags::TYPE_TITLE => [
                    $this->processUnicode($item[ 'snippet' ][ 'title' ])
                ],
            ];

        // формируем массив старых тэгов
        $oldTags = [];
        foreach ($tags as $tag)
            $oldTags[ $tag->video_id ][ (int)$tag->type ][ $tag->id ] = $tag->text;

        $transaction = Yii::$app->db->beginTransaction();

        // проанализировать тэги, добавить отсутствующие и удалить ненужные
        $delIds = [];
        $addData = [];
        foreach ($videoIds as $videoId => $videoLink) {
            if (!isset($oldTags[ $videoId ]) && !isset($newTags[ $videoId ]))
                continue;

            foreach (Tags::$weights as $type => $weight) {
                if (!isset($oldTags[ $videoId ][ $type ]) && !isset($newTags[ $videoId ][ $type ]))
                    continue;

                $addValues = array_diff((array)$newTags[ $videoId ][ $type ], (array)$oldTags[ $videoId ][ $type ]);
                $delValues = array_diff((array)$oldTags[ $videoId ][ $type ], (array)$newTags[ $videoId ][ $type ]);

                $delIds = array_merge($delIds, array_keys($delValues));

                foreach ($addValues as $value)
                    $addData[] = [
                        'video_id' => $videoId,
                        'type' => $type,
                        'text' => $value,
                    ];
            }
        }

        if (!empty($addData))
            Yii::$app->db->createCommand()->batchInsert(Tags::tableName(), array_keys($addData[ 0 ]), $addData)->execute();

        if (!empty($delIds))
            Tags::deleteAll(['id' => $delIds]);

        $profiling->duration = round(microtime(true) - $time, 2);
        $profiling->memory = memory_get_usage() / 1024 / 1024;
        $profiling->save();

        $transaction->commit();

        Yii::$app->cache->delete(PopularTags::TAGS_CACHE_KEY);

        Yii::info("Тэги обновлены, добавлено " . Yii::t('app', '{n, plural, one{# тэг} other{# тэгов}}', ['n' => count($addData)]) .
            ", удалено " . Yii::t('app', '{n, plural, one{# тэг} other{# тэгов}}', ['n' => count($delIds)]) .
            ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) .
            " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
    }

    /**
     * Обновление квот.
     */
    public function actionUpdateQuota()
    {
        $time = microtime(true);

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
                ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) .
                " сек, память: " . Yii::$app->formatter->asShortSize(memory_get_usage(), 1), 'agent');
        }
    }

    /**
     * Тестирование HighloadAPI.
     */
    public function actionTestHighload()
    {
        return false;

        $videoIds = ArrayHelper::map(Videos::find()->active()->all(), 'id', 'video_link');
        $channelsIds = ArrayHelper::map(Channels::find()->all(), 'id', 'channel_link');

        // Запрос на получение статистики
        echo "==== Запрос на получение статистики ====\n";

        echo "Обычный\n";
        $time = microtime(true);
        $response = YoutubeAPI::query('videos', ['id' => $videoIds], ['statistics', 'liveStreamingDetails'], YoutubeAPI::QUERY_MULTIPLE);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "Highload\n";
        $time = microtime(true);
        $response = HighloadAPI::query('videos', ['id' => $videoIds], ['statistics', 'liveStreamingDetails'], YoutubeAPI::QUERY_MULTIPLE);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        /*echo "\n";

        // Одиночный запрос
        echo "==== Одиночный запрос ====\n";

        echo "Обычный\n";
        $time = microtime(true);
        $response = YoutubeAPI::query('channels', ['forUsername' => 'starmedia'], ['snippet', 'statistics']);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "Highload\n";
        $time = microtime(true);
        $response = HighloadAPI::query('channels', ['forUsername' => 'starmedia'], ['snippet', 'statistics']);
        echo round(microtime(true) - $time, 2) . " сек.\n";

        echo "\n";

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

        echo "\n";

        // Постраничный запрос
        echo "==== Постраничный запрос ====\n";

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
        $time = microtime(true);

        $statistics = Statistics::getStatistics(1, [
            'timeType' => Statistics::QUERY_TIME_WEEK,
            'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF,
            'findCached' => true,
        ]);

        echo "--- Время подробно ---\n";
        foreach (Yii::getLogger()->getProfiling() as $item)
            echo "[" . round($item[ 'duration' ], 2) . "] " . $item[ 'info' ] . "\n";

        echo "Элементов: " . $statistics[ 'pagination' ][ 'count' ] . "\n";
        echo round(memory_get_usage() / 1024 / 1024, 2) . " МБ\n";
        echo round(microtime(true) - $time, 2) . " сек.\n";
    }
}
