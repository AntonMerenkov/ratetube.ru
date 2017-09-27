<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Positions */

$this->title = 'Добавить позицию';
$this->params['breadcrumbs'][] = ['label' => 'Позиции', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Добавить';
?>
<div class="positions-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
