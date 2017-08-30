<?php
use app\models\Channels;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $count int */
/* @var $channels Channels[] */

?>

<div class="widget widget-top-channels">
    <div class="widget-header">Топ-<?=$count ?> блоггеров</div>

    <div class="channel-list">
        <? foreach ($channels as $channel) : ?>
            <a class="channel-item" href="<?=Url::to(['site/index', 'channel_id' => $channel->id]) ?>">
                <div class="image" style="background-image: url('<?=$channel->image_url ?>')"></div>
                <div class="info">
                    <div class="name"><?=$channel->name ?></div>
                    <div class="description">Подписчиков: <?=number_format($channel->subscribers_count, 0, ',', ' ') ?></div>
                </div>
            </a>
        <? endforeach; ?>
    </div>
</div>
