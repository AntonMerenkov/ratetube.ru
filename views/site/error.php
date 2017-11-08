<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
?>
<article>
    <div class="site-error">

        <h1><?= Html::encode($this->title) ?></h1>

        <div class="alert alert-danger">
            <?= nl2br(Html::encode($message)) ?>
        </div>

        <p>
            Произошла ошибка при обработке вашего запроса.
        </p>
        <p>
            Если вы думаете, что это связано с неполадками на сайте - пожалуйста, свяжитесь с нами. Спасибо!
        </p>

    </div>

</article>