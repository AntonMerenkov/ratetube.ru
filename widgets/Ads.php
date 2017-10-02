<?php

namespace app\widgets;

use app\components\Statistics;
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

            $adModels = Yii::$app->cache->getOrSet('ads', function() {
                return \app\models\Ads::find()->where(['active' => 1])->all();
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