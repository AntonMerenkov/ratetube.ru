<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $channels app\models\Channels[] */
/* @var $category_id int */

$category = \app\models\Categories::find()->where(['id' => $category_id])->one();

$this->title = 'Добавление списка каналов';
$this->params['breadcrumbs'][] = ['label' => 'Каналы', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $category->name, 'url' => ['index', 'id' => $category_id]];
$this->params['breadcrumbs'][] = 'Добавление списка';
?>
<div class="channels-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <? if (!empty(Yii::$app->request->post())) : ?>
        <?=$form->errorSummary($channels) ?>
    <? endif; ?>

    <p class="help-block">Для добавления списка каналов скопируйте ваш список и вставьте его в поле ввода, затем нажмите кнопку
        «Проверить и добавить». Дождитесь окончания проверки и нажмите кнопку «Сохранить».</p>
    <p>Если в базе данных уже есть добавляемый канал, то дубликат добавлен не будет.</p>

    <?=Html::textarea('urls', '', [
        'class' => 'form-control',
        'rows' => 20
    ]) ?>

    <br>
    <a href="#" id="validate" class="btn btn-primary"><i class="glyphicon glyphicon-plus"></i> Проверить и добавить</a>
    <span id="status" style="display: inline-block; padding: 6px 12px;" class="text-muted hidden"></span>
    <br>
    <br>

    <h3>Результаты проверки</h3>
    <table id="channels-table" class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>Ссылка</th>
            <th>Наименование</th>
            <th>Изображение</th>
            <th>Подписчиков</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td colspan="4" class="empty text-muted">Ни одного канала не добавлено.</td>
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

            var controls = $('textarea[name="urls"], #validate, button[type="submit"]');

            var urls = $('textarea[name="urls"]').val().split("\n").filter(function(value) {
                return $.trim(value) != '';
            });

            // удаление дубликатов из списка
            urls = $.distinct(urls);
            $('textarea[name="urls"]').val(urls.join("\n"));
            controls.prop('disabled', true).addClass('disabled');

            if (urls.length > 0) {
                $('#status').text('0 / ' + urls.length + ' обработано').removeClass('hidden').attr('data-count', 0);
                var errorUrls = [];
                var duplicateCount = 0;

                var promises = $.map(urls, function(url){
                    return $.post('/rt--admin/channels/query-data', {url: url}).then(function(data) {
                        data = $.parseJSON(data);

                        var currentId = parseInt($('#inputs').attr('data-count'));
                        if (data.duplicate != undefined && data.duplicate) {
                            duplicateCount++;
                        } else if (data.id != undefined) {
                            $('#inputs').append($('<input type="hidden"  name="Channels[' + currentId + '][url]" value="' + url + '">'));
                            $('#inputs').append($('<input type="hidden"  name="Channels[' + currentId + '][channel_link]" value="' + data.id + '">'));
                            $('#inputs').append($('<input type="hidden"  name="Channels[' + currentId + '][name]" value="">').val(data.name));
                            $('#inputs').append($('<input type="hidden"  name="Channels[' + currentId + '][image_url]" value="' + data.image + '">'));
                            $('#inputs').append($('<input type="hidden"  name="Channels[' + currentId + '][subscribers_count]" value="' + data.subscribers_count + '">'));

                            $('#inputs').attr('data-count', currentId + 1);

                            $('#channels-table').find('tbody tr:has(td.empty)').remove();
                            $('#channels-table').find('tbody').append($('<tr>' +
                                '<td>' + url + '</td>' +
                                '<td>' + data.name + '</td>' +
                                '<td><div style="background-image: url(\'' + data.image + '\'); width: 32px; height: 32px; background-size: contain; background-position: center"></div></td>' +
                                '<td>' + numberWithCommas(data.subscribers_count) + '</td>' +
                                '</tr>'))
                        } else {
                            errorUrls.push(url);
                        }

                        $('#status').attr('data-count', parseInt($('#status').attr('data-count')) + 1);

                        $('#status').text($('#status').attr('data-count') + ' / ' + urls.length + ' обработано' +
                            (errorUrls.length > 0 ? ', ' + errorUrls.length + ' не добавлены' : '') +
                            (duplicateCount > 0 ? ', ' + duplicateCount + ' каналов уже есть в базе' : ''));
                    });
                });

                $.when.apply(this, promises)
                    .then(function(){
                        $('textarea[name="urls"]').val(errorUrls.join("\n"));
                        controls.removeAttr('disabled').removeClass('disabled');

                        if (errorUrls.length == 0 && duplicateCount == 0)
                            $('#status').addClass('hidden');
                    });
            }
        });
    });
</script>
