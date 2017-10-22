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
    public static $count = 3;

    public static $cacheTime = 3600;

    const CACHE_KEY = 'widget-videos-cache';
    const CACHE_DATE_KEY = 'widget-videos-cache-date';

    /**
     * Получение данных для отображения виджета.
     *
     * @return array
     */
    public static function getData()
    {
        $videos = Statistics::getStreaming();
        $videos = array_slice($videos, 0, self::$count);

        return $videos;
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
        Yii::beginProfile('Виджет «В эфире»');

        if (self::$count <= 0)
            self::$count = 3;

        // если нет в кэше - не загружаем виджет вообще
        $videos = Yii::$app->cache->get(self::CACHE_KEY);
        if ($videos === false)
            $videos = [];

        Yii::endProfile('Виджет «В эфире»');

        return $this->render('streaming', [
            'count' => (int) self::$count,
            'videos' => $videos,
        ]);
    }
}