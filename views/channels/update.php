<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Channels */

$this->title = 'Изменение канала: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Каналы', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['index', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Изменение';
?>
<div class="channels-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
