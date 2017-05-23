<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use app\models\Channels;
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
                    'columns' => [
                        [
                            'attribute' => 'name',
                            'format' => 'raw',
                            'value' => function($data){
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

                        /*[
                            'attribute' => 'ip_addr',
                            'contentOptions' => [
                                'class' => 'text-center'
                            ],
                            'headerOptions' => [
                                'class' => 'text-center'
                            ],
                        ],

                        [
                            'attribute' => 'mac_addr',
                            'contentOptions' => [
                                'class' => 'text-center'
                            ],
                            'headerOptions' => [
                                'class' => 'text-center'
                            ],
                        ],

                        [
                            'attribute' => 'nb_addr',
                            'contentOptions' => [
                                'class' => 'text-center'
                            ],
                            'headerOptions' => [
                                'class' => 'text-center'
                            ],
                        ],

                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => function ($data) {
                                $filesCount = count($data->files);

                                if ($filesCount == 0)
                                    return '<i class="glyphicon glyphicon-ok text-success"></i> OK';
                                else
                                    return '<i class="glyphicon glyphicon-remove text-danger"></i> ' . \Yii::t('app', '{n,plural,one{# файл} few{# файла} many{# файлов} other{# файла}}', ['n' => $filesCount]);
                            },
                            'contentOptions' => [
                                'class' => 'text-center',
                                'style' => 'min-width: 120px;'
                            ],
                            'headerOptions' => [
                                'class' => 'text-center'
                            ],
                        ],

                        [
                            'attribute' => 'last_date',
                            'format' => 'raw',
                            'value' => function ($data) {
                                $lastScan = $data->scans[ 0 ];

                                if (is_null($lastScan))
                                    return '<span class="text-muted">[нет]</span>';

                                $time = strtotime($lastScan->time);

                                $month_name = [
                                    1 => 'января',
                                    2 => 'февраля',
                                    3 => 'марта',
                                    4 => 'апреля',
                                    5 => 'мая',
                                    6 => 'июня',
                                    7 => 'июля',
                                    8 => 'августа',
                                    9 => 'сентября',
                                    10 => 'октября',
                                    11 => 'ноября',
                                    12 => 'декабря'
                                ];

                                $month = $month_name[date('n', $time)];

                                $day = date('j', $time);
                                $year = date('Y', $time);
                                $hour = date('G', $time);
                                $min = date('i', $time);

                                $dif = time() - $time;

                                if ($dif < 59) {
                                    return $dif . " сек. назад";
                                } elseif ($dif / 60 > 1 and $dif / 60 < 59) {
                                    return round($dif / 60) . " мин. назад";
                                } elseif ($dif / 3600 > 1 and $dif / 3600 < 23) {
                                    return round($dif / 3600) . " час. назад";
                                } elseif ($dif / 3600 / 24 >= 1 and $dif / 3600 / 24 < 7) {
                                    return round($dif / 3600 / 24) . " дн. назад";
                                } else {
                                    return '<span class="text-danger">' . round($dif / 3600 / 24) . " дн. назад" . '</span>';
                                }
                            },
                            'contentOptions' => [
                                'class' => 'text-center'
                            ],
                            'headerOptions' => [
                                'class' => 'text-center'
                            ],
                        ],*/

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
            <? endif; ?>
        </div>
    </div>

</div>
