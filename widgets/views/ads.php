<?php
use app\models\Channels;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $errors [] */
/* @var $ads [] */
/* @var $options [] */

?>

<? if (!empty($errors)) : ?>
    <div class="alert alert-danger">
        <?=implode('<br>', $errors) ?>
    </div>
<? else : ?>
    <div class="<?=$options[ 'class' ] ?>">
        <? foreach ($ads as $position => $model) : ?>
            <div data-position="<?=$position ?>">
                <? if ($model instanceof \app\models\Ads) : ?>
                    <a href="<?=($model->url != '' ? $model->url : '#')?>" target="_blank" rel="nofollow">
                        <img src="<?=Url::to(['ads/file', 'uuid' => $model->uuid]) ?>">
                    </a>
                <? endif; ?>
            </div>
        <? endforeach; ?>
    </div>
<? endif; ?>
