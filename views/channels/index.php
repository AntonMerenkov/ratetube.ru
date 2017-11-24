<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use app\models\Videos;
/* @var $this yii\web\View */
/* @var $searchModel app\models\ChannelsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $category app\models\Categories */
/* @var $channelDataProvider yii\data\ActiveDataProvider */

$this->title = 'Каналы';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="channels-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-xs-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    Категории
                    <?= Html::a('<i class="glyphicon glyphicon-plus"></i> Добавить', ['create-category'], [
                        'class' => 'text-success pull-right',
                    ]) ?>
                </div>
                <ul class="list-group">
                    <?= ListView::widget([
                        'dataProvider' => $dataProvider,
                        'itemView' => '/categories/_category',
                        'summary' => false,
                    ]); ?>
                </ul>
            </div>
        </div>
        <div class="col-xs-9">
            <? if (!is_null($category)) : ?>
                <h3>
                    <?=Html::encode($category->name) ?>
                    <?= Html::a('<i class="glyphicon glyphicon-remove"></i> Удалить категорию', ['delete-category', 'id' => $category->id], [
                        'class' => 'pull-right btn btn-danger',
                        'data' => [
                            'confirm' => 'Вы действительно хотите удалить данную категорию?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?=Html::a(
                        '<i class="glyphicon glyphicon-pencil"></i> Изменить категорию',
                        \yii\helpers\Url::toRoute(['update-category', 'id' => $category->id]),
                        [
                            'class' => 'pull-right btn btn-primary',
                            'style' => 'margin-right: 10px;'
                        ]
                    ); ?>
                </h3>
                <br>

                <?= GridView::widget([
                    'dataProvider' => $channelDataProvider,
                    'filterModel' => $searchModel,
                    'summary' => false,
                    'id' => 'channels-grid',
                    'columns' => [
                        [
                            'attribute' => 'name',
                            'format' => 'raw',
                            'value' => function($data) {
                                return Html::a(
                                    Html::tag('div', '', [
                                        'class' => 'channel-icon',
                                        'style' => "background-image: url('" . $data->image_url . "')"
                                    ]) .
                                    $data->name,
                                    \yii\helpers\Url::toRoute(['channels/update', 'id' => $data->id]),
                                    [
                                        'style' => 'line-height: 24px;'
                                    ]
                                );
                            },
                            'headerOptions' => [
                                'class' => 'text-center'
                            ],
                            'contentOptions' => [
                                'style' => 'min-width: 200px;'
                            ]
                        ],
                        [
                            'attribute' => 'videos',
                            'format' => 'raw',
                            'value' => function($data) {
                                $activeCount = Videos::find()->where(['channel_id' => $data->id])->active()->count();
                                $allCount = Videos::find()->where(['channel_id' => $data->id])->count();

                                return '<a href="' . Url::to(['list-videos', 'id' => $data->id]) . '"><span class="text-success">' . $activeCount . '</span>' .
                                    ($activeCount < $allCount ?
                                        '<span class="text-muted"> / ' . $allCount . '</span>' .
                                        '</a>  (<a href="' . \yii\helpers\Url::to(['channels/restore', 'id' => $data->id]) . '" onclick="return confirm(\'Вы действительно хотите вернуть неактуальные видео?\')" class="text-danger"><i class="glyphicon glyphicon-repeat"></i> вернуть</a>)' : '');
                            },
                            'headerOptions' => [
                                'class' => 'text-center'
                            ],
                            'contentOptions' => [
                                'class' => 'text-center'
                            ]
                        ],

                        [
                            'class' => 'yii\grid\ActionColumn',
                            'buttons' => [
                                'view' => function () {
                                    return false;
                                },
                                'update' => function () {
                                    return false;
                                },
                            ],
                            'contentOptions' => [
                                'style' => 'width: 24px;'
                            ],
                            'buttonOptions' => [
                                'class' => 'text-danger',
                            ]
                        ],
                    ],
                ]); ?>

                <?=Html::a(
                    '<i class="glyphicon glyphicon-plus"></i> Добавить канал',
                    \yii\helpers\Url::toRoute(['create', 'category_id' => $category->id]),
                    [
                        'class' => 'btn btn-success',
                    ]
                ); ?>

                <?=Html::a(
                    '<i class="glyphicon glyphicon-plus"></i> Добавить список каналов',
                    \yii\helpers\Url::toRoute(['create-list', 'category_id' => $category->id]),
                    [
                        'class' => 'btn btn-success',
                    ]
                ); ?>

                <?=Html::a(
                    '<i class="glyphicon glyphicon-search"></i> Поиск по ключевым словам',
                    \yii\helpers\Url::toRoute(['search', 'category_id' => $category->id]),
                    [
                        'class' => 'btn btn-info',
                    ]
                ); ?>
            <? endif; ?>
        </div>
    </div>

</div>
