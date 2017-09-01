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

/**
 * Class PopularTags
 * @package app\widgets
 *
 * Виджет для отображения самых популярных тэгов.
 */
class PopularTags extends Widget
{
    public $count = 5;
    public $slice = 50;

    const TAGS_CACHE_KEY = 'tags_list';

    public function run()
    {
        $cacheKey = self::TAGS_CACHE_KEY .
            '-' . Yii::$app->request->get('category_id', '') .
            '-' . (int) Yii::$app->request->get('channel_id', 0);

        $resultTags = Yii::$app->cache->getOrSet($cacheKey . '-rand', function() use ($cacheKey) {
            $tagsData = Yii::$app->cache->getOrSet($cacheKey, function() {
                $category_id = Yii::$app->request->get('category_id', null);
                if (!is_null($category_id))
                    $category_id = Categories::findOne(['code' => $category_id])->id;

                $channel_id = Yii::$app->request->get('channel_id', null);
                if (!is_null($channel_id))
                    $channel_id = Channels::findOne(['id' => $channel_id])->id;

                // получаем статистику с учетом канала и категории
                $statisticsQueryData = Statistics::getStatistics(1, [
                    "category_id" => $category_id,
                    "channel_id" => $channel_id,
                    'fullData' => true
                ]);

                $videoIds = array_map(function($item) {
                    return $item[ 'id' ];
                }, $statisticsQueryData[ 'data' ]);
                $videoIds = array_combine($videoIds, $videoIds);

                // загружаем все тэги по данными видео
                ini_set('memory_limit', '512M');
                $tags = Yii::$app->cache->getOrSet(self::TAGS_CACHE_KEY, function() {
                    return Yii::$app->db->createCommand('SELECT id, video_id, text from `' . Tags::tableName() . '`')->queryAll();
                }, 3600 * 12);

                // фильтруем тэги только по выбранным видео
                $tags = array_filter($tags, function($item) use ($videoIds) {
                    return isset($videoIds[ $item[ 'video_id' ] ]);
                });

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
            }, 3600 * 3);

            $tagWeights = $tagsData[ 'weights' ];
            $tagNames = $tagsData[ 'names' ];

            // сортируем и отдаем $count тэгов из последних $slice
            $tagWeights = array_slice($tagWeights, 0, $this->slice);
            $randomTags = array_keys($tagWeights);
            $randomTags = array_intersect_key($randomTags, array_fill_keys(array_rand($randomTags, $this->count), 0));

            $query = Yii::$app->request->get('query', null);

            $resultTags = [];
            foreach ($randomTags as $tag)
                $resultTags[] = [
                    'text' => $tagNames[ $tag ],
                    'weight' => $tagWeights[ $tag ],
                    'active' => !is_null($query) && (mb_strtolower($tagNames[ $tag ]) == mb_strtolower($query)),
                ];

            return $resultTags;
        }, 3600);

        // TODO: URLы для поиска по категории и по каналу (в 2 формах поиска и в ссылках тэгов)

        return $this->render('popular-tags', [
            'tags' => $resultTags,
        ]);
    }
}