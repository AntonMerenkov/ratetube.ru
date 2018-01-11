<?php

/* @var $this yii\web\View */
/* @var $statisticsQueryData [] */

use app\assets\CircleProgressAsset;
use app\models\Categories;
use app\components\Statistics;
use app\models\Channels;
use app\models\Videos;
use yii\bootstrap\Nav;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

$this->title = 'RateTube';
$channelId = Yii::$app->request->get('channel_id', null);
$query = Yii::$app->request->get('query', null);
?>

<?php $this->beginBlock('refresh-block'); ?>
    <div class="refresh-block">
        <div id="refresh-progress"></div>
        <a href="#" id="refresh-control"></a>
    </div>

    <? $this->registerJsFile('/js/refresh.js'); ?>
<?php $this->endBlock(); ?>

<? if (!is_null($channelId)) : ?>
    <? $channel = Channels::findOne(['id' => $channelId]) ?>
    <div id="channel-info">
        <div class="image" style="background-image: url('<?=$channel->image_url ?>')"></div>
        <div class="info">
            <div class="name"><?=$channel->name ?></div>
            <div class="description"><?=number_format(Videos::find()->where(['channel_id' => $channelId])->active()->count(), 0, ',', ' ') ?> видео | <?=number_format($channel->subscribers_count, 0, ',', ' ') ?> подписчиков</div>
        </div>
    </div>
<? endif; ?>

<? if (!is_null($query)) : ?>
    <div id="search-info">
        <div class="summary">
            Найдено <span class="count"><?=$statisticsQueryData[ 'pagination' ][ 'count' ] ?> видео</span> по запросу: "<span class="query"><?=$query ?></span>"
        </div>
        <form action="<?=Url::to([
            'site/index',
            //"category_id" => Yii::$app->request->get('category_id', null),
            //"channel_id" => Yii::$app->request->get('channel_id', null),
        ]) ?>" method="get">
            <div class="input-group">
                <input type="text" name="query" class="form-control" placeholder="Что вы хотите найти?" value="<?=$query ?>">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-search"></i> Найти</button>
                </span>
            </div>
        </form>
    </div>
<? endif; ?>

<?= Html::a("", [
    "site/ajax-get-statistics",
    "id" => Yii::$app->request->get('id', null),
    "category_id" => Yii::$app->request->get('category_id', null),
    "channel_id" => Yii::$app->request->get('channel_id', null),
    "query" => Yii::$app->request->get('query', null),
    "page" => $statisticsQueryData[ 'pagination' ][ 'page' ] == 1 ? null : $statisticsQueryData[ 'pagination' ][ 'page' ],
], ['class' => 'hidden', 'id' => 'refreshButton']) ?>

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
        ] + ($model[ 'special' ] == 1 ? [
            'data-special' => 1
        ] : []);
    },
    'columns' => [
        [
            'attribute' => 'name',
            'header' => 'Видеоролик',
            'format' => 'raw',
            'value' => function($data){
                return '<div class="cell-table">
                    <div class="cell-table-cell">' . Html::a(
                        '',
                        ['/site/index', 'channel_id' => $data[ 'channel' ][ 'id' ]],
                        [
                            'class' => 'channel-link',
                            'style' => $data[ 'channel' ][ 'image_url' ] ? 'background-image: url("' . $data[ 'channel' ][ 'image_url' ] . '")' : '',
                            'title' => $data[ 'channel' ][ 'name' ]
                        ]
                    ) . '</div>
                    
                    <div class="cell-table-cell">' . Html::a(
                        $data[ 'name' ],
                        '#',
                        [
                            'data-image' => $data[ 'image_url' ],
                            'class' => $data[ 'ad' ] ? 'ad' : '',
                            'data-video-id' => $data[ 'video_link' ],
                        ]
                    ) . '</div>
                    
                    <div class="cell-table-cell"><a href="#" class="info"></a></div>
                </div>';
                return Html::a(
                    $data[ 'name' ],
                    'https://www.youtube.com/watch?v=' . $data[ 'video_link' ],
                    ['target' => '_blank']
                );
            },
        ],
        [
            'attribute' => 'views_diff',
            'header' => Html::a('+ <i class="glyphicon glyphicon-eye-open"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_VIEWS_DIFF]),
            'format' => 'raw',
            'value' => function($data){
                return ($data[ 'views_diff' ] > 0 ? '+' . $data[ 'views_diff' ] : '');
            },
            'headerOptions' => [
                'class' => Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_VIEWS_DIFF ? 'active' : '',
            ],
        ],
        [
            'attribute' => 'likes_diff',
            'header' => Html::a('+ <i class="glyphicon glyphicon-hand-up"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_LIKES_DIFF]),
            'format' => 'raw',
            'value' => function($data){
                return ($data[ 'likes_diff' ] > 0 ? '+' . $data[ 'likes_diff' ] : '');
            },
            'headerOptions' => [
                'class' => Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_LIKES_DIFF ? ' active' : '',
            ],
        ],
        [
            'attribute' => 'dislikes_diff',
            'header' => Html::a('+ <i class="glyphicon glyphicon-hand-down"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_DISLIKES_DIFF]),
            'format' => 'raw',
            'value' => function($data){
                return ($data[ 'dislikes_diff' ] > 0 ? '+' . $data[ 'dislikes_diff' ] : '');
            },
            'headerOptions' => [
                'class' => Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_DISLIKES_DIFF ? ' active' : '',
            ],
        ],
        [
            'attribute' => 'views',
            'header' => Html::a('<i class="glyphicon glyphicon-eye-open"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_VIEWS]),
            'format' => 'raw',
            'value' => function($data){
                return ($data[ 'views' ] > 0 ? $data[ 'views' ] : '');
            },
            'headerOptions' => [
                'class' => Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_VIEWS ? ' active' : '',
            ],
        ],
        [
            'attribute' => 'position_diff',
            'header' => Html::a('<i class="glyphicon glyphicon-sort"></i>', ['site/ajax-set-sorting', 'id' => Statistics::SORT_TYPE_POSITION_DIFF]),
            'format' => 'raw',
            'value' => function($data){
                if ($data[ 'position_diff' ] > 0)
                    return '+' . $data[ 'position_diff' ];
                else if ($data[ 'position_diff' ] < 0)
                    return $data[ 'position_diff' ];
                else return '';
            },
            'headerOptions' => [
                'class' => Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF) == Statistics::SORT_TYPE_POSITION_DIFF ? ' active' : '',
            ],
        ],
    ],
]); ?>

<? if ($statisticsQueryData[ 'pagination' ][ 'pageCount' ] > 1) : ?>
    <div class="pagination">
        <a href="<?= ($statisticsQueryData[ 'pagination' ][ 'page' ] == 1 ? '#' : Yii::$app->urlManager->createUrl([
            "site/index" ,
            "category_id" => Yii::$app->request->get('category_id', null),
            "channel_id" => Yii::$app->request->get('channel_id', null),
            "query" => Yii::$app->request->get('query', null),
            "page" => $statisticsQueryData[ 'pagination' ][ 'page' ] - 1 == 1 ? null : $statisticsQueryData[ 'pagination' ][ 'page' ] - 1])) ?>" class="prev"
            <? if ($statisticsQueryData[ 'pagination' ][ 'page' ] == 1) : ?>disabled="disabled"<? endif; ?>>
            <i class="glyphicon glyphicon-menu-left"></i>
            <i class="glyphicon glyphicon-menu-left"></i>
        </a>
        <div class="page">Страница <?=$statisticsQueryData[ 'pagination' ][ 'page' ] ?></div>
        <a href="<?= ($statisticsQueryData[ 'pagination' ][ 'page' ] == $statisticsQueryData[ 'pagination' ][ 'pageCount' ] ? '#' : Yii::$app->urlManager->createUrl([
            "site/index" ,
            "category_id" => Yii::$app->request->get('category_id', null),
            "channel_id" => Yii::$app->request->get('channel_id', null),
            "query" => Yii::$app->request->get('query', null),
            "page" => $statisticsQueryData[ 'pagination' ][ 'page' ] + 1])) ?>" class="next"
           <? if ($statisticsQueryData[ 'pagination' ][ 'page' ] == $statisticsQueryData[ 'pagination' ][ 'pageCount' ]) : ?>disabled="disabled"<? endif; ?>>
            <i class="glyphicon glyphicon-menu-right"></i>
            <i class="glyphicon glyphicon-menu-right"></i>
        </a>
    </div>
<? endif; ?>

<? if (!isset($_COOKIE[ 'help_modal_showed' ])) : ?>
    <? /*<div id="help-modal" class="modal">
        <div class="content">
            <? require dirname(__FILE__) . '/banner.php' ?>
        </div>
    </div> */ ?>

    <script>
        function getCookie(name) {
            var matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
            ));
            return matches ? decodeURIComponent(matches[1]) : undefined;
        }

        function setCookie(name, value, options) {
            options = options || {};

            var expires = options.expires;

            if (typeof expires == "number" && expires) {
                var d = new Date();
                d.setTime(d.getTime() + expires * 1000);
                expires = options.expires = d;
            }
            if (expires && expires.toUTCString) {
                options.expires = expires.toUTCString();
            }

            value = encodeURIComponent(value);

            var updatedCookie = name + "=" + value;

            for (var propName in options) {
                updatedCookie += "; " + propName;
                var propValue = options[propName];
                if (propValue !== true) {
                    updatedCookie += "=" + propValue;
                }
            }

            document.cookie = updatedCookie;
        }

        <? /*$(function() {
            if (getCookie('help_modal_showed') == undefined) {
                $('#help-modal').modal({
                    fadeDuration: 500,
                    fadeDelay: 0.50
                });

                setCookie('help_modal_showed', 1, {
                    expires: 86400 * 365 * 10
                })
            }
        });*/ ?>
    </script>
<? endif;