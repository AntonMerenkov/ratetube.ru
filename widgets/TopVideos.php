<?php

namespace app\widgets;

use app\components\Statistics;
use app\models\Channels;
use backend\components\Backups;
use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class TopVideos
 * @package app\widgets
 *
 * Виджет для отображения топа видео.
 */
class TopVideos extends Widget
{
    public $count = 5;

    public function run()
    {
        Yii::beginProfile('Виджет «Топ 5 видео»');

        if ($this->count <= 0)
            $this->count = 5;

        $statistics = Statistics::getStatistics(1, [
            'findCached' => true,
        ]);

        $videos = array_slice($statistics[ 'data' ], 0, $this->count);

        Yii::endProfile('Виджет «Топ 5 видео»');

        return $this->render('top-videos', [
            'count' => (int) $this->count,
            'videos' => $videos,
        ]);
    }
}