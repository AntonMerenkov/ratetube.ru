<?php

namespace app\commands;

use app\models\Channels;
use app\models\Profiling;
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
        $newVideoIds = Videos::findByChannelIds($channelsIds);

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

        Yii::info("Получено новых видео: " . count(array_diff($oldVideos, array_map(function($item) {
            return $item[ 'id' ];
        }, $newVideoIds))) . ', время: ' . Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . " сек", 'agent');
    }

    /**
     * Обновление статистики по видео.
     */
    public function actionUpdateStatistics()
    {
        echo "stat";
    }
}
