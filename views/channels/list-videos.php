<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use app\models\Videos;
/* @var $this yii\web\View */
/* @var $channelModel app\models\Channels */
/* @var $searchModel app\models\VideosSearch */
/* @var $videosDataProvider yii\data\ActiveDataProvider */

$this->title = 'Список видео канала «' . $channelModel->name . '»';
$this->params['breadcrumbs'][] = ['label' => 'Каналы', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $channelModel->category->name, 'url' => ['index', 'id' => $channelModel->category_id]];
$this->params['breadcrumbs'][] = ['label' => $channelModel->name, 'url' => ['update', 'id' => $channelModel->id]];
$this->params['breadcrumbs'][] = 'Список видео';

?>
<div class="channels-index">

    <h3>
        <?= Html::encode($this->title) ?>
        <?=Html::a(
            '<i class="glyphicon glyphicon-refresh"></i> Обновить вручную',
            \yii\helpers\Url::to(['channels/reload', 'id' => $channelModel->id]),
            [
                'class' => 'pull-right btn btn-warning',
            ]
        ); ?>

        <?
        $activeCount = Videos::find()->where(['channel_id' => $channelModel->id])->active()->count();
        $allCount = Videos::find()->where(['channel_id' => $channelModel->id])->count();

        if ($activeCount < $allCount) :
        ?>
            <?=Html::a(
                '<i class="glyphicon glyphicon-repeat"></i> Вернуть все неактивные',
                \yii\helpers\Url::to(['channels/restore', 'id' => $channelModel->id]),
                [
                    'class' => 'pull-right btn btn-primary',
                    'style' => 'margin-right: 10px;',
                    'onclick' => "return confirm('Вы действительно хотите вернуть неактуальные видео?')"
                ]
            ); ?>
        <? endif; ?>
    </h3>
    <br>

    <?= GridView::widget([
        'dataProvider' => $videosDataProvider,
        'filterModel' => $searchModel,
        'summary' => false,
        'id' => 'channels-grid',
        'formatter' => ['class' => 'yii\i18n\Formatter', 'nullDisplay' => '<span class="text-muted">(нет)</span>'],
        'columns' => [
            [
                'attribute' => 'name',
                'format' => 'raw',
                'value' => function($data) {
                    return Html::a(
                        $data->name,
                        'https://www.youtube.com/watch?v=' . $data->video_link,
                        ['target' => '_blank']
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
                'attribute' => 'views',
                'header' => '<i class="glyphicon glyphicon-eye-open"></i>',
                'format' => 'raw',
                'value' => function($data){
                    return end($data->statistics)->views;
                },
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center',
                    'style' => 'width: 80px;'
                ]
            ],
            [
                'attribute' => 'likes',
                'header' => '<i class="glyphicon glyphicon-hand-up"></i>',
                'format' => 'raw',
                'value' => function($data){
                    return end($data->statistics)->likes;
                },
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center',
                    'style' => 'width: 80px;'
                ]
            ],
            [
                'attribute' => 'dislikes',
                'header' => '<i class="glyphicon glyphicon-hand-down"></i>',
                'format' => 'raw',
                'value' => function($data){
                    return end($data->statistics)->dislikes;
                },
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center',
                    'style' => 'width: 80px;'
                ]
            ],

            [
                'attribute' => 'active',
                'format' => 'raw',
                'value' => function($data) {
                    if ($data->active)
                        return '<i class="glyphicon glyphicon-ok text-success"></i>';
                    else
                        return '<i class="glyphicon glyphicon-remove text-danger"></i>';
                },
                'headerOptions' => [
                    'class' => 'text-center'
                ],
                'contentOptions' => [
                    'class' => 'text-center',
                    'style' => 'width: 80px;'
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
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="glyphicon glyphicon-trash"></i>', $url, [
                            'class' => 'btn btn-danger',
                            'data-pjax' => 0,
                            'data-confirm' => 'Вы уверены, что хотите удалить этот элемент?'
                        ]);
                    },
                ],
                'urlCreator' => function ($action, $model, $key, $index) {
                    return Url::to([$action . '-video', 'id' => $model->id]);
                },
                'contentOptions' => [
                    'style' => 'width: 24px;'
                ],
                'buttonOptions' => [
                    'class' => 'text-danger',
                ]
            ],
        ],
    ]); ?>

</div>
