<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\components\Statistics;
use app\models\Categories;
use app\widgets\PopularTags;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use app\widgets\TopChannels;

AppAsset::register($this);
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
            <div class="col-md-8">
                <div class="nav-block">
                    <div id="categories">
                        <nav>
                            <?
                            $categories = Categories::getDb()->cache(function ($db) {
                                return Categories::find()->all();
                            });

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
                    <div id="tags">
                        <?= PopularTags::widget(); ?>

                        <div id="search">
                            <form action="<?=Url::to(['site/index']) ?>" method="get">
                                <input type="text" name="query" class="form-control" placeholder="Что вы хотите найти?">
                                <a href="#"><i class="glyphicon glyphicon-search"></i> Найти</a>
                            </form>
                        </div>
                    </div>
                </div>
                <? if (isset($this->blocks['refresh-block']))
                    echo $this->blocks['refresh-block']; ?>
            </div>
            <div class="col-md-2 col-xs-6">
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
            </div>
        </div>
    </div>
</header>

<main>
    <div class="container">
        <div class="row">
            <div class="col-md-2">
                <?= TopChannels::widget() ?>

                <!--<div class="widget widget-transparent widget-ad">
                    <a href="#">
                        <img src="/data/ad-left-1.png">
                    </a>
                    <a href="#">
                        <img src="/data/ad-left-2.png">
                    </a>
                    <a href="#">
                        <img src="/data/ad-left-3.png">
                    </a>
                    <a href="#">
                        <img src="/data/ad-left-4.png">
                    </a>
                </div>-->
            </div>
            <div class="col-lg-8 col-md-10">
                <?=$content ?>
            </div>
            <div class="col-lg-2">
                <!--<div class="widget widget-streaming">
                    <div class="widget-header">В эфире</div>

                    <div class="video-list">
                        <div class="video-item">
                            <a href="#" class="channel-name">Marakasi wot</a>
                            <a href="#" class="link">
                                <img src="/data/stream-1.png" class="image">
                                <div class="name">НОВЫЙ АККАУНТ БЕЗ
                                    ДОНАТА, НАБИРАЮ
                                    РЕКРУТОВ! РАЗЫГРЫВАЮ
                                    ГОЛДУ World of Tanks</div>
                            </a>
                        </div>

                        <div class="video-item">
                            <a href="#" class="channel-name">De3epTup</a>
                            <a href="#" class="link">
                                <img src="/data/stream-2.png" class="image">
                                <div class="name">Арта Wot. В погоне за уроном:
                                    Объект 261 и GWE E100.
                                    Стрим танки.</div>
                            </a>
                        </div>

                        <div class="video-item">
                            <a href="#" class="channel-name">Game World</a>
                            <a href="#" class="link">
                                <img src="/data/stream-3.png" class="image">
                                <div class="name">WOT Мастер на все топы.</div>
                            </a>
                        </div>
                    </div>
                </div>-->

                <!--<div class="widget widget-top-videos">
                    <div class="widget-header">Топ-5 видео</div>

                    <div class="flexslider">
                        <div class="video-list">
                            <div class="video-item">
                                <a href="#" class="link">
                                    <img src="/data/stream-1.png" class="image">
                                </a>
                            </div>

                            <div class="video-item">
                                <a href="#" class="link">
                                    <img src="/data/stream-2.png" class="image">
                                </a>
                            </div>

                            <div class="video-item">
                                <a href="#" class="link">
                                    <img src="/data/stream-3.png" class="image">
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="navigation">
                        <a href="#" class="flex-prev"></a>
                        <a href="#" class="flex-next"></a>
                    </div>

                    <div class="control-nav"></div>
                </div>-->

                <!--<div class="widget widget-transparent widget-ad visible-lg-block">
                    <a href="#">
                        <img src="/data/ad-right-1.png">
                    </a>
                    <a href="#">
                        <img src="/data/ad-right-2.png">
                    </a>
                    <a href="#">
                        <img src="/data/ad-right-3.png">
                    </a>
                </div>-->
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
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-4 col-sm-6">
                        <nav>
                            <ul>
                                <li><a href="#">Статистика</a></li>
                                <li><a href="#">Сотрудничество</a></li>
                            </ul>
                        </nav>
                    </div>
                    <div class="col-md-8 col-sm-6">
                        <nav>
                            <?=Nav::widget([
                                'options' => ['class' => ''],
                                'items' => Yii::$app->user->isGuest ? [
                                    ['label' => 'Войти', 'url' => ['/site/login']]
                                ] : [
                                    ['label' => 'Статистика', 'url' => ['/statistics/index']],
                                    ['label' => 'Каналы', 'url' => ['/channels/index']],
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
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
