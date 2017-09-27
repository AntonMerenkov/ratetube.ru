<?php

use app\models\Ads;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Реклама';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ads-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'summary' => false,
        'columns' => [
            'id',
            'name',
            [
                'attribute' => 'position',
                'value' => function($model) {
                    return Ads::$positions[ $model->position ];
                },
                'contentOptions' => [
                    'style' => 'width: 200px'
                ]
            ],
            [
                'attribute' => 'imageFile',
                'format' => 'raw',
                'value' => function($model) {
                    return '<img src="' . Url::to(['ads/file', 'uuid' => $model->uuid, 'no_stat' => 1]) . '" style="max-width: 200px; max-height: 200px;">';
                },
            ],
            [
                'attribute' => 'views',
                'header' => 'Всего просмотров',
                'value' => function($model) {
                    return array_sum(ArrayHelper::map($model->adStatistics, 'id', 'views'));
                },
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '<div class="btn-group btn-group-justified">{view}{update}{delete}</div>',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<i class="glyphicon glyphicon-eye-open"></i>', $url, [
                            'class' => 'btn btn-primary'
                        ]);
                    },
                    'update' => function ($url, $model, $key) {
                        return Html::a('<i class="glyphicon glyphicon-pencil"></i>', $url, [
                            'class' => 'btn btn-success'
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="glyphicon glyphicon-trash"></i>', $url, [
                            'class' => 'btn btn-danger',
                            'data-pjax' => 1,
                            'data-method' => 'post',
                            'data-confirm' => 'Вы уверены, что хотите удалить этот элемент?'
                        ]);
                    },
                ],
                'options' => [
                    'style' => 'width: 150px;'
                ]
            ],
        ],
    ]); ?>
    <?php Pjax::end(); ?>

    <p>
        <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
</div>
