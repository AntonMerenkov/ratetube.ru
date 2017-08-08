<?php

use app\components\Statistics;
use app\models\Categories;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Channels */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="channels-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'url')->textInput() ?>

    <div id="channels-image" style="background-image: url('<?=$model->image_url ?>')"></div>

    <div id="channels-url-error" class="alert alert-danger hidden"></div>

    <p class="help-block">Укажите URL канала и остальные поля будут заполнены автоматически.</p>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'channel_link')->hiddenInput() ?>

    <p id="channels-link" class="help-block"><? if ($model->channel_link != '') : ?><?=$model->channel_link ?><? else : ?>[не получен]<? endif; ?></p>

    <?= $form->field($model, 'image_url')->hiddenInput()->label(false) ?>

    <?= $form->field($model, 'category_id')->dropDownList(ArrayHelper::map(Categories::find()->all(), 'id', 'name'),
        $model->isNewRecord ? ['disabled' => true] : []); ?>

    <br>

    <fieldset>
        <legend>Удаление видео</legend>

        <p class="text-muted">
            [Категория]
            <? if ($model->category->timeframeExist) : ?>
                Критерий удаления неактуальных видео: удалять видео, набравшие менее <?=Yii::t('app', '{n, plural, one{# просмотра} other{# просмотров}}', ['n' =>  $model->category->flush_count ]); ?> за <?=Statistics::$timeTypes[ $model->category->flush_timeframe ] ?>.
            <? else : ?>
                Неактуальные видео не удаляются.
            <? endif; ?>
        </p>
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
            $('#channels-timeframeexist').change(function() {
                if ($(this).is(':checked'))
                    $('#timeframe').removeClass('hidden');
                else
                    $('#timeframe').addClass('hidden');
            });
        </script>
    </fieldset>
    <br>

    <fieldset>
        <legend>Загрузка видео</legend>

        <?= $form->field($model, 'load_last_days')->textInput([
            'type' => 'load_last_days',
            'min' => 0
        ]) ?>
        <p class="help-block">Если вы хотите загрузить все видео с канала, укажите 0 или оставьте поле пустым.</p>
    </fieldset>
    <br>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Добавить' : 'Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
    $('#channels-url').keyup(function() {
        $.post('/admin/channels/query-data', {url: $(this).val()}).success(function(data) {
            data = $.parseJSON(data);

            if (data.error != undefined)
                $('#channels-url-error').text(data.error).removeClass('hidden');
            else
                $('#channels-url-error').addClass('hidden');

            if (data.id != undefined) {
                $('#channels-channel_link').val(data.id);
                $('#channels-link').text(data.id);
            }
            if (data.name != undefined)
                $('#channels-name').val(data.name);
            if (data.image != undefined) {
                $('#channels-image').css('background-image', "url('" + data.image + "')");
                $('#channels-image_url').val(data.image);
            }
        });
    });

    $('.channels-form form').submit(function() {
        $('#channels-channel_link').removeProp('disabled');
    });
</script>
