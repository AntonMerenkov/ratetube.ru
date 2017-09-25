<?php

use app\models\Ads;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Ads */
/* @var $statisticsProvider \yii\data\ArrayDataProvider */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Реклама', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ads-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            [
                'attribute' => 'position',
                'value' => Ads::$positions[ $model->position ],
            ],
            [
                'attribute' => 'imageFile',
                'format' => 'raw',
                'value' => '<img src="' . Url::to(['ads/file', 'uuid' => $model->uuid, 'no_stat' => 1]) . '"">'
            ],
        ],
    ]) ?>

    <? if ($statisticsProvider->count > 0) : ?>
        <h3>Статистика</h3>

        <?= GridView::widget([
            'dataProvider' => $statisticsProvider,
            'formatter' => [
                'class' => 'yii\i18n\Formatter',
                'nullDisplay' => '<span class="not-set">(нет)</span>'
            ],
            'summary' => false,
            'columns' => [
                [
                    'attribute' => 'date',
                    'header' => 'Дата',
                ],
                [
                    'attribute' => 'views',
                    'header' => 'Кол-во показов',
                ],
            ],
        ]); ?>
    <? endif; ?>

</div>
