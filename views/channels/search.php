<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $channels app\models\Channels[] */
/* @var $category_id int */

$category = \app\models\Categories::find()->where(['id' => $category_id])->one();

$this->title = 'Поиск каналов';
$this->params['breadcrumbs'][] = ['label' => 'Каналы', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $category->name, 'url' => ['index', 'id' => $category_id]];
$this->params['breadcrumbs'][] = 'Поиск каналов';
?>
<div class="channels-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <? if (!empty(Yii::$app->request->post())) : ?>
        <?=$form->errorSummary($channels) ?>
    <? endif; ?>

    <p class="help-block">Для поиска каналов укажите ключевое слово и нажмите «Найти и добавить».
        Дождитесь окончания проверки и нажмите кнопку «Сохранить».</p>
    <p>Если в базе данных уже есть добавляемый канал, то дубликат добавлен не будет.</p>

    <div class="form-group">
        <label>Отфильтровать каналы с минимальным количеством подписчиков</label>
        <?=Html::input('number', 'subscribers', '25000', [
            'class' => 'form-control',
        ]) ?>
    </div>

    <div class="form-group">
        <label>Проанализировать пунктов поисковой выдачи</label>
        <?=Html::input('number', 'count', '100', [
            'class' => 'form-control',
        ]) ?>
    </div>

    <br>

    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">Запрос</h3>
        </div>
        <div class="panel-body">
            <?=Html::textInput('query', '', [
                'class' => 'form-control',
            ]) ?>

            <br>
            <a href="#" id="validate" class="btn btn-primary"><i class="glyphicon glyphicon-search"></i> Найти и добавить</a>
            <span id="status" style="display: inline-block; padding: 6px 12px;" class="text-muted hidden"></span>
        </div>
    </div>

    <br>
    <br>

    <h3>Результаты поиска</h3>
    <table id="channels-table" class="table table-striped table-bordered">
        <thead>
        <tr>
            <th></th>
            <th>Ссылка</th>
            <th>Наименование</th>
            <th>Изображение</th>
            <th>Подписчиков</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td colspan="5" class="empty text-muted">Ни одного канала не добавлено.</td>
        </tr>
        </tbody>
    </table>
    <div id="inputs" data-count="0"></div>

    <br>
    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
    function numberWithCommas(x) {
        x = x.toString();
        var pattern = /(-?\d+)(\d{3})/;
        while (pattern.test(x))
            x = x.replace(pattern, "$1 $2");
        return x;
    }

    $(function() {
        $.extend({
            distinct : function(anArray) {
                var result = [];
                $.each(anArray, function(i,v){
                    if ($.inArray(v, result) == -1) result.push(v);
                });
                return result;
            }
        });

        $('#validate').click(function(e) {
            e.preventDefault();

            var controls = $('input[name="subscribers"], input[name="count"], input[name="query"], #validate, button[type="submit"]');
            controls.prop('disabled', true).addClass('disabled');

            $('#status').text('Обработка...').removeClass('hidden').attr('data-count', 0);
            var errorUrls = [];
            var duplicateCount = 0;

            $.post('/rt--admin/channels/search-data', {
                subscribers: $('input[name="subscribers"]').val(),
                count: $('input[name="count"]').val(),
                query: $('input[name="query"]').val()
            }).then(function(data) {
                data = $.parseJSON(data);

                if (data.error != undefined) {
                    $('#status').text(data.error);
                    return true;
                }

                var currentId = parseInt($('#inputs').attr('data-count'));
                for (var i in data.items) {
                    currentId++;

                    $('#inputs').append($('<input type="hidden" name="Channels[' + currentId + '][url]" value="' + data.items[ i ].url + '">'));
                    $('#inputs').append($('<input type="hidden" name="Channels[' + currentId + '][channel_link]" value="' + data.items[ i ].id + '">'));
                    $('#inputs').append($('<input type="hidden" name="Channels[' + currentId + '][name]" value="">').val(data.items[ i ].name));
                    $('#inputs').append($('<input type="hidden" name="Channels[' + currentId + '][image_url]" value="' + data.items[ i ].image_url + '">'));
                    $('#inputs').append($('<input type="hidden" name="Channels[' + currentId + '][subscribers_count]" value="' + data.items[ i ].subscribers_count + '">'));

                    $('#channels-table').find('tbody tr:has(td.empty)').remove();
                    $('#channels-table').find('tbody').append($('<tr>' +
                        '<td><input type="checkbox" name="Channels[' + currentId + '][checked]" value="1"' + (data.items[ i ].exists == 0 ? ' checked' : '') + '></td>' +
                        '<td>' + data.items[ i ].url + '</td>' +
                        '<td>' + data.items[ i ].name + '</td>' +
                        '<td><div style="background-image: url(\'' + data.items[ i ].image_url + '\'); width: 32px; height: 32px; background-size: contain; background-position: center"></div></td>' +
                        '<td>' + numberWithCommas(data.items[ i ].subscribers_count) + '</td>' +
                        '</tr>'));

                    $('#inputs').attr('data-count', currentId);
                }

                controls.removeAttr('disabled').removeClass('disabled');
                $('#status').addClass('hidden');
                $('input[name="query"]').val('');
            })
        });
    });
</script>
