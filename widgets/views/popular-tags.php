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
            $('#tags').jQCloud(<?=json_encode($tags)?>, {
                colors: ["#67c1f5", "#5fb1e0", "#57a0cb", "#4f8fb6", "#477ea0", "#3f6e8b", "#375d76", "#2f4c61"],
                fontSize: {
                    from: 0.18,
                    to: 0.05
                },
                delay: 50,
                encodeURI: false
            });
        </script>
    </div>
<? endif; ?>
