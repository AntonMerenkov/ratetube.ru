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
            foreach ($newVideoIds as $videoData) {
                if (in_array($videoData[ 'id' ], $oldVideos))
                    continue;

                $video = new Videos();
                $video->name = mb_substr($videoData[ 'title' ], 0, 255);
                $video->video_link = $videoData[ 'id' ];
                $video->channel_id = array_search($videoData[ 'channel_id' ], $channelsIds);
                $video->save();
            }

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

        $transaction = Videos::getDb()->beginTransaction();

        try {
            foreach ($videoStatistics as $videoId => $videoData) {
                if (!in_array($videoId, $videoIds))
                    continue;

                $statistics = new Statistics();
                $statistics->datetime = $profiling->datetime;
                $statistics->video_id = array_search($videoId, $videoIds);
                $statistics->views = $videoData[ 'viewCount' ];
                $statistics->likes = $videoData[ 'likeCount' ];
                $statistics->dislikes = $videoData[ 'dislikeCount' ];
                $statistics->save();
            }

            $profiling->duration = Yii::$app->formatter->asDecimal(microtime(true) - $time, 2);
            $profiling->save();

            $transaction->commit();
        } catch(\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        Yii::info("Получена статистика для " . count($videoIds) . " видео, время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . " сек", 'agent');
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

        // за 14 дней
        $sql = "delete
                from statistics
                where datetime < '" . date('Y-m-d H:i:s', time() - 86400 * 14) . "'";

        $oldCount = Yii::$app->db->createCommand("select count(*) from statistics")->queryScalar();
        Yii::$app->db->createCommand($sql)->execute();
        $newCount = Yii::$app->db->createCommand("select count(*) from statistics")->queryScalar();

        $profiling->duration = Yii::$app->formatter->asDecimal(microtime(true) - $time, 2);
        $profiling->save();

        Yii::info("Таблица статистики очищена, " . ($newCount - $oldCount) . " рядов удалено, время: " . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . " сек", 'agent');
    }
}
