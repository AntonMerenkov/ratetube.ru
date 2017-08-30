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
use yii\widgets\Pjax;

$this->title = 'RateTube';
$channelId = Yii::$app->request->get('channel_id', null);
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
            <div class="description"><?=number_format(Videos::find()->where(['channel_id' => $channelId])->count(), 0, ',', ' ') ?> видео | <?=number_format($channel->subscribers_count, 0, ',', ' ') ?> подписчиков</div>
        </div>
    </div>
<? endif; ?>

<?= Html::a("", [
    "site/ajax-get-statistics",
    "id" => Yii::$app->request->get('id', null),
    "category_id" => Yii::$app->request->get('category_id', null),
    "channel_id" => Yii::$app->request->get('channel_id', null),
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
        ];
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
                            'style' => 'background-image: url("' . $data[ 'channel' ][ 'image_url' ] . '")',
                            'title' => $data[ 'channel' ][ 'name' ]
                        ]
                    ) . '</div>
                    
                    <div class="cell-table-cell">' . Html::a(
                        $data[ 'name' ],
                        'https://www.youtube.com/watch?v=' . $data[ 'video_link' ],
                        ['target' => '_blank']
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
                        "category_id" => Yii::$app->request->get('category_id', null),
                        "channel_id" => Yii::$app->request->get('channel_id', null),
                        "page" => $pages[ $i ] == 1 ? null : $pages[ $i ]]) ?>"><?=$pages[ $i ] ?></a>
                </li>
                <? if (isset($pages[ $i + 1 ]) && $pages[ $i ] + 1 < $pages[ $i + 1 ]) : ?>
                    <li class="disabled"><a href="#"><span aria-hidden="true">...</span></a></li>
                <? endif; ?>
            <? endfor; ?>
        </ul>
    </nav>
<? endif; ?>
