<?php

namespace app\widgets;

use app\components\Statistics;
use app\models\Channels;
use backend\components\Backups;
use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class Streaming
 * @package app\widgets
 *
 * Виджет для отображения видео в эфире.
 */
class Streaming extends Widget
{
    public $count = 3;

    public function run()
    {
        Yii::beginProfile('Виджет «В эфире»');

        if ($this->count <= 0)
            $this->count = 3;

        $videos = Statistics::getStreaming();
        $videos = array_slice($videos, 0, $this->count);

        Yii::endProfile('Виджет «В эфире»');

        return $this->render('streaming', [
            'count' => (int) $this->count,
            'videos' => $videos,
        ]);
    }
}