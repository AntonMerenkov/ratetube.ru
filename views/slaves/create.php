<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Slaves */

$this->title = 'Добавить сервер';
$this->params['breadcrumbs'][] = ['label' => 'Сервера', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="slaves-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
