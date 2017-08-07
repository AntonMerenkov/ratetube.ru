<?php

namespace app\commands;

use app\models\Channels;
use app\models\Profiling;
use app\models\Statistics;
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
     */
    public function actionUpdateVideos()
    {
        $time = microtime(true);

        $profiling = new Profiling();
        $profiling->code = 'agent-update-videos';
        $profiling->datetime = date('d.m.Y H:i:s', round($time / 10) * 10);

        $channelsIds = ArrayHelper::map(Channels::find()->all(), 'id', 'channel_link');

        $oldVideos = ArrayHelper::map(Videos::find()->all(), 'id', 'video_link');
        $newVideoIds = Videos::getByChannelIds($channelsIds);

        $transaction = Videos::getDb()->beginTransaction();

        try {
            $values = [];

            foreach ($newVideoIds as $videoData) {
                if (in_array($videoData[ 'id' ], $oldVideos))
                    continue;

                $values[] = [
                    'name' => mb_substr($videoData[ 'title' ], 0, 255),
                    'video_link' => $videoData[ 'id' ],
                    'channel_id' => array_search($videoData[ 'channel_id' ], $channelsIds),
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

        $videoIds = ArrayHelper::map(Videos::find()->all(), 'id', 'video_link');
        $videoStatistics = Statistics::getByVideoIds($videoIds);

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
}
