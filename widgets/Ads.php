<?php

namespace app\widgets;

use app\components\Statistics;
use app\models\Categories;
use app\models\Channels;
use backend\components\Backups;
use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Class Ads
 * @package app\widgets
 *
 * Виджет для отображения рекламы.
 */
class Ads extends Widget
{
    public $positions = [];
    public $options = [
        'class' => 'widget widget-transparent widget-ad'
    ];

    public function run()
    {
        $errors = [];
        $ads = [];

        if (empty($this->positions)) {
            $errors[] = 'Позиции для отображения рекламы не указаны.';
        } else {
            Yii::beginProfile('Виджет «Реклама»');

            $categoryId = Yii::$app->request->get('category_id', null);
            $cacheId = 'ads-' . implode('-', [
                $categoryId
            ]);

            $adModels = Yii::$app->cache->getOrSet($cacheId, function() use ($categoryId) {
                $ads = \app\models\Ads::find()->where(['active' => 1])->all();

                if (!is_null($categoryId)) {
                    $category = Categories::find()->where(['code' => $categoryId])->one();

                    if ($categoryId) {
                        $id = $category->id;

                        $ads = array_values(array_filter($ads, function($item) use ($id) {
                            return empty($item->categoriesIds) || in_array($id, $item->categoriesIds);
                        }));
                    }
                }

                return $ads;
            }, 600);

            $ads = array_fill_keys($this->positions, []);

            foreach ($adModels as $adModel)
                if (isset($ads[ $adModel->position ]))
                    $ads[ $adModel->position ][] = $adModel;

            foreach ($ads as $position => $values) {
                if (!empty($values)) {
                    $ads[ $position ] = $values[ array_rand($values) ];
                }
            }

            Yii::endProfile('Виджет «Реклама»');
        }

        return $this->render('ads', [
            'ads' => $ads,
            'errors' => $errors,
            'options' => $this->options,
        ]);
    }
}