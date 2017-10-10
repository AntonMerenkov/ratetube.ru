<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ApiKeys */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="api-keys-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'key')->textInput(['maxlength' => true]) ?>

    <div id="message" class="alert hidden"></div>

    <script>
        $('#apikeys-key').keyup(function() {
            $.post('/admin/api-keys/query-data', {key: $(this).val()}).success(function(data) {
                data = $.parseJSON(data);

                if (data.status == 1) {
                    $('#message').removeClass('hidden').removeClass('alert-danger').addClass('alert-success')
                        .text('Ключ API действителен.');
                } else {
                    $('#message').removeClass('hidden').removeClass('alert-success').addClass('alert-danger')
                        .text('Ключ API недействителен.')
                }
            });
        });

        $('.channels-form form').submit(function() {
            $('#channels-channel_link').removeProp('disabled');
        });
    </script>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Добавить' : 'Изменить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
