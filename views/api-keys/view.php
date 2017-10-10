<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\ApiKeys */

$this->title = '#' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'YouTube API', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="api-keys-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'key',
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
            'rowOptions' => function($item) {
                if ($item[ 'quota' ] >= 1000000)
                    return [
                        'class' => 'danger'
                    ];

                return [];
            },
            'columns' => [
                [
                    'attribute' => 'date',
                    'header' => 'Дата',
                ],
                [
                    'attribute' => 'quota',
                    'header' => 'Использовано квот',
                    'value' => function($item) {
                        return Yii::$app->formatter->asDecimal(min($item[ 'quota' ], 1000000));
                    },
                ],
                [
                    'attribute' => 'quota_percent',
                    'header' => '%',
                    'value' => function($item) {
                        return Yii::$app->formatter->asPercent(min($item[ 'quota' ], 1000000) / 1000000);
                    },
                ],
            ],
        ]); ?>
    <? endif; ?>

</div>
