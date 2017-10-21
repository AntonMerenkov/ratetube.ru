<?php

namespace app\widgets;

use app\components\Statistics;
use app\models\Channels;
use backend\components\Backups;
use Yii;
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
    public static $count = 10;
    public static $interval = Statistics::QUERY_TIME_WEEK;
    public static $cacheTime = 3600 * 4;

    const CACHE_KEY = 'widget-top-channel-cache';
    const CACHE_DATE_KEY = 'widget-top-channel-cache-date';

    /**
     * Получение данных для отображения виджета.
     *
     * @return array
     */
    public static function getData()
    {
        // получаем список всех блогеров с кол-вом подписчиков
        $channels = ArrayHelper::map(Channels::find()->all(), 'id', function($item) {
            return $item;
        });

        $statistics = Statistics::getStatistics(1, [
            'timeType' => self::$interval,
            'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF,
            'fullData' => true,
            'findCached' => true,
        ]);

        // строим статистику
        $viewCount = [];
        foreach ($statistics[ 'data' ] as $item)
            $viewCount[ $item[ 'channel' ][ 'id' ] ] += $item[ 'views_diff' ];

        arsort($viewCount);

        $topChannels = [];
        foreach (array_slice(array_keys($viewCount), 0, self::$count) as $id)
            $topChannels[] = $channels[ $id ];

        return $topChannels;
    }

    /**
     * Обновление кэша виджета.
     */
    public static function updateCache()
    {
        $cacheValid = true;

        if (!Yii::$app->cache->exists(self::CACHE_KEY))
            $cacheValid = false;

        if (time() - Yii::$app->cache->get(self::CACHE_DATE_KEY) > self::$cacheTime)
            $cacheValid = false;

        if ($cacheValid)
            return false;

        $data = self::getData();
        Yii::$app->cache->set(self::CACHE_KEY, $data);
        Yii::$app->cache->set(self::CACHE_DATE_KEY, time());
        unset($data);

        return true;
    }

    public function run()
    {
        Yii::beginProfile('Виджет «Топ 10 блоггеров»');

        if (self::$count <= 0)
            self::$count = 10;

        // если нет в кэше - не загружаем виджет вообще
        $topChannels = Yii::$app->cache->get(self::CACHE_KEY);
        if ($topChannels === false)
            $topChannels = [];

        Yii::endProfile('Виджет «Топ 10 блоггеров»');

        return $this->render('top-channels', [
            'count' => (int) self::$count,
            'channels' => $topChannels,
        ]);
    }
}