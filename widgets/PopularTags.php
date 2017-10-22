<?php

namespace app\widgets;

use app\components\Statistics;
use app\models\Categories;
use app\models\Channels;
use app\models\Tags;
use backend\components\Backups;
use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class PopularTags
 * @package app\widgets
 *
 * Виджет для отображения самых популярных тэгов.
 */
class PopularTags extends Widget
{
    public $count = 15;
    public $slice = 50;

    public static $cacheTime = 3600 * 4;

    const CACHE_KEY = 'widget-tags-cache';
    const CACHE_ARRAY_KEY = 'widget-tags-data';
    const CACHE_DATE_KEY = 'widget-tags-cache-date';

    /**
     * Получение данных для отображения виджета.
     *
     * @return array
     */
    public static function getData()
    {
        // пусть тэги будут одинаковыми для всех страниц, зато кэш будет един
        /*$category_id = Yii::$app->request->get('category_id', null);
        if (!is_null($category_id))
            $category_id = Categories::findOne(['code' => $category_id])->id;

        $channel_id = Yii::$app->request->get('channel_id', null);
        if (!is_null($channel_id))
            $channel_id = Channels::findOne(['id' => $channel_id])->id;*/

        // получаем статистику с учетом канала и категории
        $statisticsQueryData = Statistics::getStatistics(1, [
            //"category_id" => $category_id,
            //"channel_id" => $channel_id,
            "timeType" => Statistics::QUERY_TIME_DAY,
            "sortType" => Statistics::SORT_TYPE_VIEWS_DIFF,
            'fullData' => true,
            'findCached' => true,
        ]);

        $videoIds = array_map(function($item) {
            return $item[ 'id' ];
        }, array_slice($statisticsQueryData[ 'data' ], 0, 1000));

        echo round(memory_get_peak_usage() / 1024 / 1024, 2) . " МБ\n";

        // загружаем все тэги по данными видео
        $tags = Yii::$app->cache->getOrSet(self::CACHE_ARRAY_KEY, function() use ($videoIds) {
            return Yii::$app->db->createCommand('SELECT id, video_id, text from `' . Tags::tableName() . '` WHERE video_id in (' . implode(',', $videoIds) . ') LIMIT 10000')->queryAll();
        }, self::$cacheTime * 3);

        echo round(memory_get_peak_usage() / 1024 / 1024, 2) . " МБ\n";

        // по каждому тексту тэга собираем кол-во баллов (1 балл - 1 diff)
        $sortType = $statisticsQueryData[ 'db' ][ 'sort_type' ];
        $weights = ArrayHelper::map($statisticsQueryData[ 'data' ], 'id', $sortType);

        foreach ($tags as $id => $tag)
            $tags[ $id ][ 'weight' ] += $weights[ $tag[ 'video_id' ] ];

        // ищем одинаковые тэги (без учета регистра)
        usort($tags, function($a, $b) {
            return $b[ 'weight' ] - $a[ 'weight' ];
        });

        $tagWeights = [];
        $tagNames = [];
        foreach ($tags as $tag) {
            if (mb_strlen($tag[ 'text' ]) <= 3)
                continue;

            $tag[ 'text' ] = trim(reset(preg_split('/[-\:\,\.\;\(\)]/ui', $tag[ 'text' ], -1, PREG_SPLIT_NO_EMPTY)));

            if (!isset($tagNames[ mb_strtolower($tag[ 'text' ]) ])) {
                $tagNames[ mb_strtolower($tag[ 'text' ]) ] = $tag[ 'text' ];
                $tagWeights[ mb_strtolower($tag[ 'text' ]) ] = $tag[ 'weight' ];
            } else {
                $tagWeights[ mb_strtolower($tag[ 'text' ]) ] += $tag[ 'weight' ];
            }
        }

        arsort($tagWeights);

        return [
            'weights' => $tagWeights,
            'names' => $tagNames,
        ];
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
        Yii::beginProfile('Виджет «Популярные тэги»');

        /*$cacheKey = self::CACHE_KEY . '-' . implode('-', [
            Yii::$app->request->get('category_id', ''),
            (int) Yii::$app->request->get('channel_id', 0),
            $this->count
        ]);

        $resultTags = Yii::$app->cache->getOrSet($cacheKey . '-rand', function() use ($cacheKey) {
            $tagsData = self::getData();

            $tagWeights = $tagsData[ 'weights' ];
            $tagNames = $tagsData[ 'names' ];

            // сортируем и отдаем $count тэгов из последних $slice
            $resultTags = [];
            if (!empty($tagWeights)) {
                $tagWeights = array_slice($tagWeights, 0, $this->slice);
                $randomTags = array_keys($tagWeights);
                $randomTags = array_intersect_key($randomTags, array_fill_keys(array_rand($randomTags, $this->count), 0));

                foreach ($randomTags as $tag)
                    $resultTags[] = [
                        'text' => $tagNames[ $tag ],
                        'weight' => $tagWeights[ $tag ],
                    ];
            }

            return $resultTags;
        }, 3600);*/

        // если нет в кэше - не загружаем виджет вообще
        $tagsData = Yii::$app->cache->get(self::CACHE_KEY);
        if ($tagsData === false)
            $tagsData = [];

        //////
        $tagWeights = $tagsData[ 'weights' ];
        $tagNames = $tagsData[ 'names' ];

        // сортируем и отдаем $count тэгов из последних $slice
        $resultTags = [];
        if (!empty($tagWeights)) {
            $tagWeights = array_slice($tagWeights, 0, $this->slice);
            $randomTags = array_keys($tagWeights);
            $randomTags = array_intersect_key($randomTags, array_fill_keys(array_rand($randomTags, $this->count), 0));

            foreach ($randomTags as $tag)
                $resultTags[] = [
                    'text' => $tagNames[ $tag ],
                    'weight' => $tagWeights[ $tag ],
                ];
        }
        //////

        $query = Yii::$app->request->get('query', null);
        foreach ($resultTags as $id => $tag)
            if (!is_null($query) && (mb_strtolower($tag[ 'text' ]) == mb_strtolower($query)))
                $resultTags[ $id ][ 'active' ] = true;

        foreach ($resultTags as $id => $tag) {
            $resultTags[ $id ][ 'link' ] = Url::to([
                'site/index',
                "category_id" => Yii::$app->request->get('category_id', null),
                "channel_id" => Yii::$app->request->get('channel_id', null),
                "query" => $tag[ 'text' ]
            ]);
        }

        Yii::endProfile('Виджет «Популярные тэги»');

        return $this->render('popular-tags', [
            'tags' => $resultTags,
        ]);
    }
}