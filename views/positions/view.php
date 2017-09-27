<?php

use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Positions */
/* @var $statisticsProvider \yii\data\ArrayDataProvider */

$this->title = $model->video->name;
$this->params['breadcrumbs'][] = ['label' => 'Позиции', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="positions-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'video_id',
                'format' => 'raw',
                'value' => Html::a(
                    $model->video->name,
                    'https://www.youtube.com/watch?v=' . $model->video->video_link,
                    ['target' => '_blank']
                )
            ],
            'position',
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
