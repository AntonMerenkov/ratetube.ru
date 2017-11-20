<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\SecurityIp */

$this->title = 'Добавление IP-адреса';
$this->params['breadcrumbs'][] = ['label' => 'Защита по IP-адресу', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="security-ip-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
