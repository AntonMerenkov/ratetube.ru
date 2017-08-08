<?php

use app\components\Statistics;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Categories */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="departments-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>

    <br>

    <fieldset>
        <legend>Удаление видео</legend>

        <?= $form->field($model, 'timeframeExist')->checkbox() ?>
        <div id="timeframe"<? if (!$model->timeframeExist) : ?> class="hidden"<? endif; ?>>
            <?= $form->field($model, 'flush_timeframe')->dropDownList([null => '[не выбран]'] + array_map(function($item) {
                    return Statistics::$timeTypes[ $item ];
                }, array_combine(array_keys(Statistics::$timeDiffs), array_keys(Statistics::$timeDiffs)))) ?>
            <?= $form->field($model, 'flush_count')->textInput([
                'type' => 'number',
                'min' => 1
            ]) ?>
        </div>
        <script>
            $('#categories-timeframeexist').change(function() {
                if ($(this).is(':checked'))
                    $('#timeframe').removeClass('hidden');
                else
                    $('#timeframe').addClass('hidden');
            });
        </script>
    </fieldset>
    <br>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
