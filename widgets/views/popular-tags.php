<?php
use app\models\Channels;
use yii\helpers\Json;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $tags [] */

?>

<? if (!empty($tags)) : ?>
    <div class="widget widget-tags">
        <div id="tags"></div>
        <script>
            $('#tags').jQCloud(<?=Json::encode($tags)?>, {
                colors: ["#67c1f5", "#5fb1e0", "#57a0cb", "#4f8fb6", "#477ea0", "#3f6e8b", "#375d76", "#2f4c61"],
                fontSize: {
                    from: 0.18,
                    to: 0.05
                },
                delay: 50
            });
        </script>
    </div>

    <!--<nav>
        <ul>
            <?/* foreach ($tags as $tag) : */?>
                <li<?/* if ($tag[ 'active' ]) : */?> class="active"<?/* endif; */?>>
                    <a href="<?/*=Url::to([
                        'site/index',
                        "category_id" => Yii::$app->request->get('category_id', null),
                        "channel_id" => Yii::$app->request->get('channel_id', null),
                        "query" => $tag[ 'text' ]
                    ])*/?>"><?/*=$tag[ 'text' ] */?></a>
                </li>
            <?/* endforeach; */?>
        </ul>
    </nav>-->
<? endif; ?>
