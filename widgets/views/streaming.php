<?php
use app\models\Channels;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $count int */
/* @var $videos [] */

?>

<div class="widget widget-streaming<? if (empty($videos)) : ?> hidden<? endif; ?>">
    <div class="widget-header">В эфире</div>

    <div class="video-list">
        <? foreach ($videos as $video) : ?>
            <div class="video-item">
                <a href="<?=Url::to(['site/index', 'channel_id' => $video[ 'channel' ][ 'id' ]]) ?>" class="channel-name"><?=$video[ 'channel' ][ 'name' ] ?></a>
                <a href="https://www.youtube.com/watch?v=<?=$video[ 'video_link' ] ?>" class="link">
                    <img src="<?=$video[ 'image_url' ] ?>" class="image">
                    <div class="name"><?=$video[ 'name' ] ?></div>
                </a>
            </div>
        <? endforeach; ?>
    </div>
</div>

