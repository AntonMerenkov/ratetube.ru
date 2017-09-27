<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Позиции';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="positions-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'summary' => false,
            'columns' => [
                'id',

                [
                    'attribute' => 'video_id',
                    'format' => 'raw',
                    'value' => function($model) {
                        return Html::a(
                            $model->video->name,
                            'https://www.youtube.com/watch?v=' . $model->video->video_link,
                            ['target' => '_blank']
                        );
                    }
                ],
                'position',
                [
                    'attribute' => 'views',
                    'header' => 'Всего просмотров',
                    'value' => function($model) {
                        return array_sum(ArrayHelper::map($model->positionStatistics, 'id', 'views'));
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
