<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Slaves */

$this->title = 'Изменить сервер: ' . $model->ip;
$this->params['breadcrumbs'][] = ['label' => 'Сервера', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->ip;
?>
<div class="slaves-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
