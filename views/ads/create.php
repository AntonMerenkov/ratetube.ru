<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Ads */

$this->title = 'Добавить рекламу';
$this->params['breadcrumbs'][] = ['label' => 'Реклама', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Добавление';
?>
<div class="ads-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
