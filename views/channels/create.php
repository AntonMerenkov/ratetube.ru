<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Channels */

$this->title = 'Добавление канала';
$this->params['breadcrumbs'][] = ['label' => 'Каналы', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->category->name, 'url' => ['index', 'id' => $model->category_id]];
$this->params['breadcrumbs'][] = 'Добавление';
?>
<div class="channels-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
