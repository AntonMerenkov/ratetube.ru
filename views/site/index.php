<?php

/* @var $this yii\web\View */
/* @var $statisticsQueryData [] */

use app\models\Categories;
use app\models\Statistics;
use yii\bootstrap\Nav;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'RateTube';
?>

<?php $this->beginBlock('categories'); ?>
    <?
    $categories = Categories::getDb()->cache(function ($db) {
        return Categories::find()->all();
    });

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-categories hidden-xs'],
        'items' => ArrayHelper::map($categories, 'id', function($item) {
            return [
                'label' => $item->name,
                'url' => ['/site/index', 'id' => $item->code]
            ];
        }),
    ]);
    ?>
<?php $this->endBlock(); ?>

<?php $this->beginBlock('update-time'); ?>
    <?
    $statisticTypes = [];

    foreach (Statistics::$timeTypes as $id => $name)
        $statisticTypes[] = [
            'label' => $name,
            'options' => [
                'data-id' => $id
            ],
            'active' => Yii::$app->session->get(Statistics::SESSION_KEY, Statistics::QUERY_TIME_HOUR) == $id
        ];

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-time hidden-xs hidden-sm'],
        'items' => $statisticTypes,
    ]);
    ?>
<?php $this->endBlock(); ?>

<style>
    body {
        background: url('/img/background.png') center top no-repeat;
    }
</style>

<div class="site-index">
    <div class="row">
        <div class="col-sm-2 hidden-xs hidden-sm">
            Место для виджета
        </div>
        <div class="col-sm-8" id="table-block">
            <?php Pjax::begin(); ?>
            <?
            $statisticsDataProvider = new ArrayDataProvider([
                'allModels' => $statisticsQueryData[ 'data' ],
                'pagination' => false
            ]);
            ?>
            <?= GridView::widget([
                'dataProvider' => $statisticsDataProvider,
                'summary' => false,
                'tableOptions' => [
                    'class' => 'table table-condensed'
                ],
                'columns' => [
                    [
                        'attribute' => 'name',
                        'header' => 'Видеоролик',
                        'format' => 'raw',
                        'value' => function($data){
                            return Html::a(
                                $data[ 'name' ],
                                'https://www.youtube.com/watch?v=' . $data[ 'video_link' ],
                                [
                                    'target' => '_blank'
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
                        'attribute' => 'views_diff',
                        'header' => '<i class="glyphicon glyphicon-eye-open"></i>',
                        'format' => 'raw',
                        'value' => function($data){
                            return ($data[ 'views_diff' ] > 0 ? '+' . $data[ 'views_diff' ] : '');
                        },
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'style' => 'max-width: 60px; font-size: 12px;',
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'likes_diff',
                        'header' => '<i class="glyphicon glyphicon-hand-up"></i>',
                        'format' => 'raw',
                        'value' => function($data){
                            return ($data[ 'likes_diff' ] > 0 ? '+' . $data[ 'likes_diff' ] : '');
                        },
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'style' => 'max-width: 60px; font-size: 12px;',
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'dislikes_diff',
                        'header' => '<i class="glyphicon glyphicon-hand-down"></i>',
                        'format' => 'raw',
                        'value' => function($data){
                            return ($data[ 'dislikes_diff' ] > 0 ? '+' . $data[ 'dislikes_diff' ] : '');
                        },
                        'headerOptions' => [
                            'class' => 'text-center'
                        ],
                        'contentOptions' => [
                            'style' => 'max-width: 60px; font-size: 12px;',
                            'class' => 'text-center'
                        ]
                    ],
                ],
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
        <div class="col-sm-2 hidden-xs hidden-sm">
            Место для виджета
        </div>
    </div>
</div>
