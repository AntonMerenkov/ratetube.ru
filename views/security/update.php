<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\SecurityIp */

$this->title = 'Изменение: ' . $model->ip;
$this->params['breadcrumbs'][] = ['label' => 'Защита по IP-адресу', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->ip;
?>
<div class="security-ip-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
