<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\components\Statistics;
use app\models\Categories;
use app\widgets\Ads;
use app\widgets\PopularTags;
use app\widgets\Streaming;
use app\widgets\TopVideos;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use app\widgets\TopChannels;

AppAsset::register($this);

$categories = Categories::getDb()->cache(function ($db) {
    return Categories::find()->all();
});

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<header>
    <div class="container">
        <div class="row">
            <div class="col-md-2 col-xs-6">
                <a href="<?=Yii::$app->homeUrl ?>" class="logo" title="RateTube">
                    <img src="/img/logo.png">
                </a>
            </div>
            <div class="col-md-8 col-xs-6">
                <div class="nav-block">
                    <div id="timeframes">
                        <nav>
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
                                'options' => ['class' => ''],
                                'items' => $statisticTypes,
                            ]);
                            ?>
                        </nav>
                    </div>
                    <? if (isset($this->blocks['refresh-block']))
                        echo $this->blocks['refresh-block']; ?>
                </div>
            </div>
            <div class="col-md-2 col-xs-6">
                <div class="contact-list">
                    <div class="contact-item email">
                        <a href="mailto:<?=Yii::$app->params[ 'email' ] ?>"><?=Yii::$app->params[ 'email' ] ?></a>
                    </div>
                </div>
            </div>
        </div>
        <div id="mobile-menu">
            <button class="btn btn-warning btn-block"><i class="glyphicon glyphicon-chevron-down"></i> Меню</button>
            <div class="content" style="display: none">
                <div class="categories">
                    <nav>
                        <?
                        echo Nav::widget([
                            'items' => ArrayHelper::map($categories, 'id', function($item) {
                                return [
                                    'label' => $item->name,
                                    'url' => ['/site/index', 'category_id' => $item->code]
                                ];
                            }),
                        ]);
                        ?>
                    </nav>
                </div>
                <div class="search">
                    <form action="<?=Url::to([
                        'site/index',
                        "category_id" => Yii::$app->request->get('category_id', null),
                        "channel_id" => Yii::$app->request->get('channel_id', null),
                    ]) ?>" method="get">
                        <input type="text" name="query" class="form-control" placeholder="Что вы хотите найти?">
                        <a href="#"><i class="glyphicon glyphicon-search"></i></a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<main>
    <div class="container">
        <div class="row">
            <div class="col-md-2">
                <div class="widget widget-categories">
                    <nav>
                        <?
                        echo Nav::widget([
                            'items' => ArrayHelper::map($categories, 'id', function($item) {
                                return [
                                    'label' => $item->name,
                                    'url' => ['/site/index', 'category_id' => $item->code]
                                ];
                            }),
                        ]);
                        ?>
                    </nav>
                </div>

                <?= PopularTags::widget(); ?>

                <div class="widget widget-search">
                    <div class="widget-header">Поиск</div>
                    <form action="<?=Url::to([
                        'site/index',
                        //"category_id" => Yii::$app->request->get('category_id', null),
                        //"channel_id" => Yii::$app->request->get('channel_id', null),
                    ]) ?>" method="get">
                        <input type="text" name="query" class="form-control">
                        <a href="#"><i class="glyphicon glyphicon-search"></i></a>
                    </form>
                </div>

                <?//= TopVideos::widget() ?>

                <?= Ads::widget([
                    'positions' => [
                        \app\models\Ads::POSITION_LEFT_1,
                        \app\models\Ads::POSITION_LEFT_2,
                        \app\models\Ads::POSITION_LEFT_3,
                        \app\models\Ads::POSITION_LEFT_4,
                    ]
                ]) ?>
            </div>
            <div class="col-lg-8 col-md-10">
                <?=$content ?>
            </div>
            <div class="col-lg-2">
                <?= TopChannels::widget() ?>
                <?= Streaming::widget() ?>

                <?= Ads::widget([
                    'positions' => [
                        \app\models\Ads::POSITION_RIGHT_1,
                        \app\models\Ads::POSITION_RIGHT_2,
                        \app\models\Ads::POSITION_RIGHT_3,
                    ],
                    'options' => [
                        'class' => 'widget widget-transparent widget-ad visible-lg-block'
                    ]
                ]) ?>
            </div>
        </div>
    </div>
</main>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-2 col-xs-6">
                <a href="<?=Yii::$app->homeUrl ?>" class="logo">
                    <img src="/img/logo.png">
                </a>
            </div>
            <div class="col-md-2 col-xs-6">
                <div class="contact-list">
                    <div class="contact-item phone">
                        <a href="callto:<?=preg_replace('/[^\d\+]/', '', Yii::$app->params[ 'phone' ]) ?>"><?=Yii::$app->params[ 'phone' ] ?></a>
                    </div>
                    <div class="contact-item email">
                        <a href="mailto:<?=Yii::$app->params[ 'email' ] ?>"><?=Yii::$app->params[ 'email' ] ?></a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-lg-3 col-md-4">
                        <nav>
                            <ul>
                                <li><a href="#">Статистика</a></li>
                                <li><a href="#">Сотрудничество</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="col-lg-9 col-md-8">
                        <nav>
                            <?=Nav::widget([
                                'options' => ['class' => ''],
                                'items' => Yii::$app->user->isGuest ? [
                                    //['label' => 'Войти', 'url' => ['/site/login']]
                                ] : [
                                    ['label' => 'Статистика', 'url' => ['/statistics/index']],
                                    ['label' => 'Каналы', 'url' => ['/channels/index']],
                                    ['label' => 'Реклама', 'url' => ['/ads/index']],
                                    ['label' => 'Позиции', 'url' => ['/positions/index']],
                                    '<li>'
                                    . Html::beginForm(['/site/logout'], 'post')
                                    . Html::submitButton(
                                        'Выйти (' . Yii::$app->user->identity->username . ')',
                                        ['class' => 'btn btn-link logout']
                                    )
                                    . Html::endForm()
                                    . '</li>'
                                ],
                            ]); ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <? include dirname(__FILE__) . '/metrics.php' ?>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
