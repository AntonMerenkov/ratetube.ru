<?php
use app\models\Channels;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $count int */
/* @var $videos [] */

?>

<div class="widget widget-top-videos">
    <div class="widget-header">Топ-5 видео</div>

    <div class="flexslider">
        <div class="video-list" data-count="<?=$count ?>">
            <? foreach ($videos as $video) : ?>
                <div class="video-item" data-id="<?=$video[ 'id' ] ?>">
                    <a href="https://www.youtube.com/watch?v=<?=$video[ 'video_link' ] ?>" class="link" target="_blank">
                        <img src="<?=$video[ 'image_url' ] ?>" class="image">
                    </a>
                </div>
            <? endforeach; ?>
        </div>
    </div>

    <div class="navigation">
        <a href="#" class="flex-prev"></a>
        <a href="#" class="flex-next"></a>
    </div>

    <div class="control-nav"></div>
</div>