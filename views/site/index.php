<?php

/* @var $this yii\web\View */
/* @var $statisticsQueryData [] */

use app\assets\CircleProgressAsset;
use app\models\Categories;
use app\components\Statistics;
use yii\bootstrap\Nav;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\Pjax;

$this->title = 'RateTube';

CircleProgressAsset::register($this);
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
    <div id="circle" style="margin-top: 8px; margin-left: 20px; display: inline-block"></div>

<?
$script = <<< JS
$(document).ready(function() {
    function updateStatistics() {
        /**
         * Обновление позиций 
         */
        $.ajax({
            url: $('#refreshButton').attr('href'),
            dataType: 'json'
        }).success(function(newData) {
            // превращаем верстку в аьсолютную
            $('#news-table').css({
                height: $('#news-table').height() + 'px',
                position: 'relative'
            });
            
            var firstColWidth = $('#news-table thead th:first-child').outerWidth();
            $('#news-table').find('tbody tr').each(function() {
                $(this).css({
                    position: 'absolute',
                    top: $(this).position().top,
                    left: 0,
                    right: 0
                }).find('td:first-child').css('width', firstColWidth + 'px');        
            });
            
            var positions = $('#news-table').find('tbody tr').map(function() {
                return $(this).position().top;
            }); 
            
            // меняем структуру согласно новым данным
            var rows = $('#news-table').find('tbody tr');
            var oldIds = $.makeArray(rows.map(function() {
                return parseInt($(this).attr('data-id'));
            }));
            var newIds = newData.map(function(item) {
                return parseInt(item.id);
            });
            
            // анимация
            // скрываем старые элементы
            for (var i in oldIds) {
                if (newIds.indexOf(oldIds[ i ]) == -1) {
                    rows.eq(i).animate({opacity: 0}, 400, 'swing', function() {
                        $(this).addClass('hidden');
                    });
                }
            }
            
            for (var i in newIds) {
                if (newIds[ i ] != oldIds[ i ]) {
                    if (oldIds.indexOf(newIds[ i ]) == -1) {
                        // добавляем новый элемент
                        $('#news-table').find('tbody').append($('<tr data-id="' + newData[ i ].id + '" class="warning" style="position: absolute; top: ' + positions[ i ] + 'px; left: 0px; right: 0px;">' +
                            '<td style="min-width: 200px; width: 693px;">' +
                            '<a href="https://www.youtube.com/watch?v=' + newData[ i ].video_link + '" target="_blank">' + newData[ i ].name + '</a>' +
                            '</td>' +
                            '<td class="text-center" style="width: 60px; max-width: 60px; font-size: 12px;">' + (newData[ i ].views_diff > 0 ? '+' + newData[ i ].views_diff : "") + '</td>' +
                            '<td class="text-center" style="width: 60px; max-width: 60px; font-size: 12px;">' + (newData[ i ].likes_diff > 0 ? '+' + newData[ i ].likes_diff : "") + '</td>' +
                            '<td class="text-center" style="width: 60px; max-width: 60px; font-size: 12px;">' + (newData[ i ].dislikes_diff > 0 ? '+' + newData[ i ].dislikes_diff : "") + '</td>' +
                            '<td class="text-center" style="width: 80px; max-width: 80px; font-size: 12px;">' + (newData[ i ].likes > 0 ? '+' + newData[ i ].likes : "") + '</td>' +
                            '</tr>'));
                    } else {
                        // изменяем позицию элемента
                        $('#news-table').find('tbody tr').eq(oldIds.indexOf(newIds[ i ])).animate({top: positions[ i ]});
                    }
                }
            }
            
            // устанавливаем новые значения статистики для существующих элементов
            for (var i in newData) {
                console.log(newData[ i ]);
                var row = rows.filter('[data-id="' + newData[ i ].id + '"]');
                
                if (row.find('td').eq(1).text() != (newData[ i ].views_diff == 0 ? '' : '+' + newData[ i ].views_diff))
                    row.find('td').eq(1).css({
                        color: 'rgba(51, 51, 51, 0)',
                        transition: 'none'
                    }).text(newData[ i ].views_diff == 0 ? '' : '+' + newData[ i ].views_diff).animate({
                        color: 'rgba(51, 51, 51, 1)'
                    });
                
                if (row.find('td').eq(2).text() != (newData[ i ].likes_diff == 0 ? '' : '+' + newData[ i ].likes_diff))
                    row.find('td').eq(2).css({
                        color: 'rgba(51, 51, 51, 0)',
                        transition: 'none'
                    }).text(newData[ i ].likes_diff == 0 ? '' : '+' + newData[ i ].likes_diff).animate({
                        color: 'rgba(51, 51, 51, 1)'
                    });
                
                if (row.find('td').eq(3).text() != (newData[ i ].dislikes_diff == 0 ? '' : '+' + newData[ i ].dislikes_diff))
                    row.find('td').eq(3).css({
                        color: 'rgba(51, 51, 51, 0)',
                        transition: 'none'
                    }).text(newData[ i ].dislikes_diff == 0 ? '' : '+' + newData[ i ].dislikes_diff).animate({
                        color: 'rgba(51, 51, 51, 1)'
                    });
                
                if (row.find('td').eq(4).text() != (newData[ i ].likes == 0 ? '' : newData[ i ].likes))
                    row.find('td').eq(4).css({
                        color: 'rgba(51, 51, 51, 0)',
                        transition: 'none'
                    }).text(newData[ i ].likes == 0 ? '' : newData[ i ].likes).css({
                        color: 'rgba(51, 51, 51, 1)'
                    });
            }
            
            // статичная замена
            setTimeout(function() {
                // удаляем невидимые элементы
                $('#news-table').find('tbody tr.hidden').remove();
                
                rows = $('#news-table').find('tbody tr');
                oldIds = $.makeArray(rows.map(function() {
                    return parseInt($(this).attr('data-id'));
                }));
                
                for (var i in newIds)
                    if (newIds[ i ] != oldIds[ i ]) {
                        rows.eq(oldIds.indexOf(newIds[ i ])).insertBefore(rows.eq(i));
                        
                        rows = $('#news-table').find('tbody tr');
                        oldIds = $.makeArray(rows.map(function() {
                            return parseInt($(this).attr('data-id'));
                        }));
                    }
                
                $('#news-table').find('tbody tr').removeAttr('style').removeClass('warning');
                $('#news-table').removeAttr('style');            
            }, 2000);
        });
    }
    
    $('#circle').on('circle-animation-end', function(event, progress) {
        if ($('#circle').circleProgress('value') == 1) {
            $('#circle').circleProgress({
                value: 0,
                animation: { duration: 0, easing: "swing" }
            });
            $('#circle').circleProgress({
                value: 1,
                animation: { duration: 10000, easing: "swing" }
            });
            
            updateStatistics();
        }
    });
    
    var circle = $('#circle').circleProgress({
        startAngle: -Math.PI / 6 * 3,
        value: 1,
        size: 32,
        fill: {
            gradient: ["#ffa500"]
        },
        animation: { duration: 10000, easing: "swing" }
    });
});
JS;
$this->registerJs($script);
?>

    <?
    $statisticTypes = [];

    foreach (Statistics::$timeTypes as $id => $name)
        $statisticTypes[] = [
            'label' => $name,
            'url' => ['/site/ajax-set-time', 'id' => $id],
            'options' => [
                'data-id' => $id
            ],
            'active' => Yii::$app->session->get(Statistics::TIME_SESSION_KEY, Statistics::QUERY_TIME_HOUR) == $id
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
        <div class="col-sm-1 hidden-xs hidden-sm">
            Место для виджета
        </div>
        <div class="col-sm-10" id="table-block">
            <?= Html::a("", [
                "site/ajax-get-statistics",
                "id" => Yii::$app->request->get('id', null),
                "page" => $statisticsQueryData[ 'pagination' ][ 'page' ] == 1 ? null : $statisticsQueryData[ 'pagination' ][ 'page' ],
            ], ['class' => 'hidden', 'id' => 'refreshButton']) ?>

            <style>
                <?
                if (Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_VIEWS_DIFF)
                    $colIndex = 2;
                else if (Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_LIKES_DIFF)
                    $colIndex = 3;
                else if (Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_DISLIKES_DIFF)
                    $colIndex = 4;
                else
                    $colIndex = 5;
                ?>
                #news-table tbody tr td:nth-child(<?=$colIndex ?>) {
                    background-color: rgba(60, 118, 61, 0.1);
                }
            </style>

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
                    'class' => 'table table-condensed',
                    'id' => 'news-table'
                ],
                'rowOptions' => function ($model, $key, $index, $grid) {
                    return [
                        'data-id' => $model[ 'id' ]
                    ];
                },
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
                            'class' => 'text-center',
                        ],
                        'contentOptions' => [
                            'style' => 'min-width: 200px;'
                        ]
                    ],
                    [
                        'attribute' => 'views_diff',
                        'header' => Html::a('+ <i class="glyphicon glyphicon-eye-open"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_VIEWS_DIFF]),
                        'format' => 'raw',
                        'value' => function($data){
                            return ($data[ 'views_diff' ] > 0 ? '+' . $data[ 'views_diff' ] : '');
                        },
                        'headerOptions' => [
                            'class' => 'text-center' . (Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_VIEWS_DIFF ? ' text-success' : ''),
                            'style' => 'width: 60px; max-width: 60px;',
                        ],
                        'contentOptions' => [
                            'style' => 'width: 60px; max-width: 60px; font-size: 12px;',
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'likes_diff',
                        'header' => Html::a('+ <i class="glyphicon glyphicon-hand-up"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_LIKES_DIFF]),
                        'format' => 'raw',
                        'value' => function($data){
                            return ($data[ 'likes_diff' ] > 0 ? '+' . $data[ 'likes_diff' ] : '');
                        },
                        'headerOptions' => [
                            'class' => 'text-center' . (Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_LIKES_DIFF ? ' text-success' : ''),
                            'style' => 'width: 60px; max-width: 60px;',
                        ],
                        'contentOptions' => [
                            'style' => 'width: 60px; max-width: 60px; font-size: 12px;',
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'dislikes_diff',
                        'header' => Html::a('+ <i class="glyphicon glyphicon-hand-down"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_DISLIKES_DIFF]),
                        'format' => 'raw',
                        'value' => function($data){
                            return ($data[ 'dislikes_diff' ] > 0 ? '+' . $data[ 'dislikes_diff' ] : '');
                        },
                        'headerOptions' => [
                            'class' => 'text-center' . (Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_DISLIKES_DIFF ? ' text-success' : ''),
                            'style' => 'width: 60px; max-width: 60px;',
                        ],
                        'contentOptions' => [
                            'style' => 'width: 60px; max-width: 60px; font-size: 12px;',
                            'class' => 'text-center'
                        ]
                    ],
                    [
                        'attribute' => 'views',
                        'header' => Html::a('<i class="glyphicon glyphicon-eye-open"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_VIEWS]),
                        'format' => 'raw',
                        'value' => function($data){
                            return ($data[ 'views' ] > 0 ? $data[ 'views' ] : '');
                        },
                        'headerOptions' => [
                            'class' => 'text-center' . (Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_VIEWS ? ' text-success' : ''),
                            'style' => 'width: 80px; max-width: 80px;',
                        ],
                        'contentOptions' => [
                            'style' => 'width: 80px; max-width: 80px; font-size: 12px;',
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
                                    "page" => $pages[ $i ] == 1 ? null : $pages[ $i ]]) ?>"><?=$pages[ $i ] ?></a>
                            </li>
                            <? if (isset($pages[ $i + 1 ]) && $pages[ $i ] + 1 < $pages[ $i + 1 ]) : ?>
                                <li class="disabled"><a href="#"><span aria-hidden="true">...</span></a></li>
                            <? endif; ?>
                        <? endfor; ?>
                    </ul>
                </nav>
            <? endif; ?>
        </div>
        <div class="col-sm-1 hidden-xs hidden-sm">
            Место для виджета
        </div>
    </div>
</div>
