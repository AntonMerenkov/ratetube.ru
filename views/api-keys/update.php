<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ApiKeys */

$this->title = 'Изменить ключ API: #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'YouTube API', 'url' => ['index']];
$this->params['breadcrumbs'][] = '#' . $model->id;
?>
<div class="api-keys-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
