<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Slaves */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="slaves-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'ip')->textInput(['maxlength' => true]) ?>

    <div id="message" class="alert hidden"></div>

    <script>
        $('#slaves-ip').keyup(function() {
            $('#message').addClass('hidden');

            $.post('/admin/slaves/query-data', {ip: $(this).val()}).success(function(data) {
                data = $.parseJSON(data);

                if (data.status == 1) {
                    $('#message').removeClass('hidden').removeClass('alert-danger').addClass('alert-success')
                        .text('Сервер корректно настроен.');
                } else {
                    $('#message').removeClass('hidden').removeClass('alert-success').addClass('alert-danger')
                        .text(data.error)
                }
            });
        });
    </script>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
