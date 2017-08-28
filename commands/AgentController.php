<?php

namespace app\commands;

use app\models\Categories;
use app\models\Channels;
use app\models\Profiling;
use app\components\Statistics;
use app\models\Videos;
use yii\console\Controller;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Агент для анализа видео на YouTube.
 */
class AgentController extends Controller
{
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
            $oldVideos = ArrayHelper::map(Videos::find()->where(['channel_id' => $channel_id])->all(), 'id', 'video_link');
        else
            $oldVideos = ArrayHelper::map(Videos::find()->all(), 'id', 'video_link');

        $newVideoIds = Videos::getByChannelIds($channelsIds);

        $transaction = Videos::getDb()->beginTransaction();

        try {
            $values = [];

            foreach ($newVideoIds as $videoData) {
                if (in_array($videoData[ 'id' ], $oldVideos))
                    continue;

                $channelId = array_search($videoData[ 'channel_id' ], $channelsIds);

                if (($loadLastDays[ $channelId ] > 0) && (time() - strtotime($videoData[ 'date' ]) > $loadLastDays[ $channelId ] * 86400))
                    continue;

                $values[] = [
                    'name' => mb_substr($videoData[ 'title' ], 0, 255),
                    'video_link' => $videoData[ 'id' ],
                    'channel_id' => $channelId,
                ];
            }

            if (!empty($values))
                Yii::$app->db->createCommand()->batchInsert(Videos::tableName(), array_keys($values[ 0 ]), $values)->execute();

            $profiling->duration = Yii::$app->formatter->asDecimal(microtime(true) - $time, 2);
            $profiling->save();

            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        Yii::info("Получено новых видео: " . count(array_diff(array_map(function($item) {
            return $item[ 'id' ];
        }, $newVideoIds), $oldVideos)) . ', время: ' . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . " сек", 'agent');
    }

    /**
     * Обновление статистики по видео.
     */
    public function actionUpdateStatistics()
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
            $lastQueryTime = array_map(function($item) {
                return 0;
            }, Statistics::$tableModels);

            foreach (array_keys($lastQueryTime) as $key) {
                $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];
                $lastQueryTime[ $key ] = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName())->queryScalar();
            }

            $addedIntervals = [];
            foreach (array_keys($lastQueryTime) as $key) {
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
                    ];
                }

                if (!empty($values)) {
                    $addedIntervals[] = strtoupper(substr($key, 0, 1));

                    Yii::$app->db->createCommand()->batchInsert($tableModel::tableName(), array_keys($values[ 0 ]), $values)->execute();
                }
            }

            $profiling->duration = Yii::$app->formatter->asDecimal(microtime(true) - $time, 2);
            $profiling->save();

            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        Yii::info("Получена статистика для " . count($videoIds) . " видео, время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . " сек, интервалы: " . implode("", $addedIntervals), 'agent');
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

        $minQueryDate = array_map(function($item) {
            return 0;
        }, Statistics::$tableModels);

        $statisticTables = [];
        foreach (array_keys($minQueryDate) as $key) {
            $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];
            $statisticTables[] = $tableModel::tableName();

            $lastDate = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName())->queryScalar();
            $minQueryDate[ $key ] = Yii::$app->db->createCommand('select MAX(datetime) from ' . $tableModel::tableName() . ' where datetime <= "' .
                date('Y-m-d H:i:s', strtotime($lastDate) - Statistics::$timeDiffs[ $key ]) . '"')->queryScalar();
        }

        if (count(array_filter($minQueryDate, function($item) {
            return !is_null($item);
        })) == 0)
            return true;

        $oldTableSize = array_sum(array_map(function($item) {
            return $item[ 'DATA_LENGTH' ] + $item[ 'INDEX_LENGTH' ];
        }, array_filter(Statistics::getTableSizeData(), function($item) use ($statisticTables) {
            return in_array($item[ 'TABLE_NAME' ], $statisticTables);
        })));

        $transaction = Yii::$app->db->beginTransaction();

        foreach ($minQueryDate as $key => $date) {
            if (is_null($date))
                continue;

            $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $key ];
            Yii::$app->db->createCommand('delete from ' . $tableModel::tableName() . ' where datetime < "' . $date . '"')->execute();
        }

        $newTableSize = array_sum(array_map(function($item) {
            return $item[ 'DATA_LENGTH' ] + $item[ 'INDEX_LENGTH' ];
        }, array_filter(Statistics::getTableSizeData(), function($item) use ($statisticTables) {
            return in_array($item[ 'TABLE_NAME' ], $statisticTables);
        })));

        $profiling->duration = Yii::$app->formatter->asDecimal(microtime(true) - $time, 2);
        $profiling->save();

        $transaction->commit();

        Yii::info("Таблицы статистики очищены, " . Yii::$app->formatter->asShortSize($oldTableSize - $newTableSize, 1) . " удалено, время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . " сек", 'agent');
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
        $channelCriteria = ArrayHelper::map(Channels::find()->with('category')->all(), 'id', function($item) {
            return $item->flush_timeframe != '' ? [
                'flush_timeframe' => $item->flush_timeframe,
                'flush_count' => $item->flush_count,
            ] : [
                'flush_timeframe' => $item->category->flush_timeframe,
                'flush_count' => $item->category->flush_count,
            ];
        });

        $framesData = array_unique(array_filter(array_map(function($item) {
            return $item[ 'flush_timeframe' ];
        }, $channelCriteria), function($item) {
            return $item != '';
        }));

        if (empty($framesData))
            return true;

        $framesData = array_map(function($item) {
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

                if ($videoData[ 'views_diff'  ] < $channelCriteria[ $videoData[ 'channel' ][ 'id' ] ][ 'flush_count' ])
                    $videoIds[] = $videoData[ 'id' ];
            }
        }

        if (empty($videoIds))
            return true;

        foreach ($framesData as $frameData)
            Yii::$app->cache->delete($frameData[ 'db' ][ 'cache_id' ]);

        Videos::updateAll(['active' => 0], 'id IN (' . implode(',', $videoIds) . ')');

        Yii::info(Yii::t('app', '{n, plural, one{# видео помечено как неактуальное} other{# видео помечены как неактуальные}}', ['n' =>  count($videoIds) ]) . ", время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . " сек", 'agent');
    }
}
