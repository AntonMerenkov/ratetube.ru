<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Positions */

$this->title = 'Изменение позиции: ' . $model->video->name;
$this->params['breadcrumbs'][] = ['label' => 'Позиции', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->video->name];
?>
<div class="positions-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
