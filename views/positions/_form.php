<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Positions */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="positions-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'url')->textInput() ?>
    <?= $form->field($model, 'videoLink')->hiddenInput()->label(false) ?>
    <?= $form->field($model, 'videoName')->hiddenInput()->label(false) ?>
    <?= $form->field($model, 'imageUrl')->hiddenInput()->label(false) ?>

    <div id="positions-url-error" class="alert alert-danger hidden"></div>
    <div id="positions-url-success" class="alert alert-success hidden"></div>

    <?= $form->field($model, 'position')->input('number', [
        'min' => 1,
        'max' => 50,
    ]) ?>

    <script>
        $(function() {
            $('input[name="Positions[url]"]').keyup(function () {
                $.post('/admin/positions/query-data', {url: $(this).val()}).success(function(data) {
                    data = $.parseJSON(data);

                    $('#positions-url-success').addClass('hidden');

                    if (data.error != undefined) {
                        $('#positions-url-error').text(data.error).removeClass('hidden');
                    } else {
                        $('#positions-url-error').addClass('hidden');

                        if (data.id != undefined)
                            $('#positions-videolink').val(data.id);
                        if (data.name != undefined)
                            $('#positions-videoname').val(data.name);
                        if (data.image != undefined)
                            $('#positions-imageurl').val(data.image);

                        if (data.name != undefined)
                            $('#positions-url-success').html('<h4>Видео найдено</h4>' + data.name).removeClass('hidden');
                    }
                });
            });
        });
    </script>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
