<?php
use app\models\Channels;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $tags [] */

?>

<? if (!empty($tags)) : ?>
    <nav>
        <ul>
            <? foreach ($tags as $tag) : ?>
                <li<? if ($tag[ 'active' ]) : ?> class="active"<? endif; ?>>
                    <a href="<?=Url::to([
                        'site/index',
                        "category_id" => Yii::$app->request->get('category_id', null),
                        "channel_id" => Yii::$app->request->get('channel_id', null),
                        "query" => $tag[ 'text' ]
                    ])?>"><?=$tag[ 'text' ] ?></a>
                </li>
            <? endforeach; ?>
        </ul>
    </nav>
<? endif; ?>
