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

            <? if ($statisticsQueryData[ 'pagination' ][ 'pageCount' ] > 1) : ?>
                <nav>
                    <ul class="pagination">
                        <?
                        $pages = array_fill($statisticsQueryData[ 'pagination' ][ 'page' ] - 2, 5, 1) +
                            array_fill(1, 5, 1) +
                            array_fill($statisticsQueryData[ 'pagination' ][ 'pageCount' ] - 2, 3, 1);

                        foreach ($pages as $key => $value)
                            if ($key < 1 || $key > $statisticsQueryData[ 'pagination' ][ 'pageCount' ])
                                unset($pages[ $key ]);

                        ksort($pages);
                        $pages = array_keys($pages);
                        ?>

                        <? for ($i = 0; $i <= count($pages) - 1; $i++) : ?>
                            <li<? if ($statisticsQueryData[ 'pagination' ][ 'page' ] == $pages[ $i ]) : ?> class="active"<? endif; ?>>
                                <a href="<?= Yii::$app->urlManager->createUrl([
                                    "site/index" ,
                                    "id" => Yii::$app->request->get('id', null),
                                    "page" => $pages[ $i ]]) ?>"><?=$pages[ $i ] ?></a>
                            </li>
                            <? if (isset($pages[ $i + 1 ]) && $pages[ $i ] + 1 < $pages[ $i + 1 ]) : ?>
                                <li class="disabled"><a href="#"><span aria-hidden="true">...</span></a></li>
                            <? endif; ?>
                        <? endfor; ?>
                    </ul>
                </nav>
            <? endif; ?>

            <?php Pjax::end(); ?>
        </div>
        <div class="col-sm-2 hidden-xs hidden-sm">
            Место для виджета
        </div>
    </div>
</div>
