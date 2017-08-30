<?php

namespace app\widgets;

use app\components\Statistics;
use app\models\Channels;
use backend\components\Backups;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class TopChannels
 * @package app\widgets
 *
 * Виджет для отображения топа каналов.
 *
 * Виджет «Топ 10 блоггеров» будет работать по алгоритму: кто получил наибольшее количество
 * суммарных просмотров его видео за неделю, тот и будет занимать более высокое место. В виджете
 * будут отображаться названия каналов и их иконки, по клику сработает фильтр «Сортировка по каналу».
 */
class TopChannels extends Widget
{
    public $count = 10;
    public $interval = Statistics::QUERY_TIME_WEEK;

    public function run()
    {
        if ($this->count <= 0)
            $this->count = 10;

        // получаем список всех блогеров с кол-вом подписчиков
        $channels = ArrayHelper::map(Channels::find()->all(), 'id', function($item) {
            return $item;
        });

        $statistics = Statistics::getStatistics(1, [
            'timeType' => $this->interval,
            'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF,
            'fullData' => true
        ]);

        // строим статистику
        $viewCount = [];
        foreach ($statistics[ 'data' ] as $item)
            $viewCount[ $item[ 'channel' ][ 'id' ] ] += $item[ 'views_diff' ];

        arsort($viewCount);

        $topChannels = [];
        foreach (array_slice(array_keys($viewCount), 0, $this->count) as $id)
            $topChannels[] = $channels[ $id ];

        return $this->render('top-channels', [
            'count' => (int) $this->count,
            'channels' => $topChannels,
        ]);
    }
}