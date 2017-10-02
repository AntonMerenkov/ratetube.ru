<?php

use app\models\Ads;
use app\models\Categories;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Ads */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="ads-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'position')->dropDownList([null => '[Не выбрано]'] + Ads::$positions, [
        'class' => 'form-control'
    ]) ?>

    <?= $form->field($model, 'imageFile')->fileInput() ?>

    <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'categoriesIds')->checkboxList(ArrayHelper::map(Categories::find()->all(), 'id', 'name'), [
        'multiple' => true,
        'separator' => '<br>',
    ]) ?>
    <p class="text-muted">
        Если не выбрана ни одна категория, то реклама будет показана в любой категории.<br>
        На главной странице реклама будет показана всегда, если она активна.
    </p>

    <?= $form->field($model, 'active')->checkbox() ?>

    <br>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
