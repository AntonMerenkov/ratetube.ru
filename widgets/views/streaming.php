<?php
use app\models\Channels;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $count int */
/* @var $videos [] */

?>

<div class="widget widget-streaming<? if (empty($videos)) : ?> hidden<? endif; ?>">
    <div class="widget-header">В эфире</div>

    <div class="video-list" data-count="<?=$count ?>">
        <? foreach ($videos as $video) : ?>
            <div class="video-item" data-id="<?=$video[ 'id' ] ?>">
                <a href="<?=Url::to(['site/index', 'channel_id' => $video[ 'channel' ][ 'id' ]]) ?>" class="channel-name"><?=$video[ 'channel' ][ 'name' ] ?></a>
                <a href="#" data-video-id="<?=$video[ 'video_link' ] ?>" class="link">
                    <div class="image" style="background-image: url('<?=$video[ 'image_url' ] ?>')"></div>
                    <div class="name"><?=$video[ 'name' ] ?></div>
                </a>
            </div>
        <? endforeach; ?>
    </div>
</div>

