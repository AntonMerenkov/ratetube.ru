<?php

/* @var $this yii\web\View */
/* @var $videosDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsQueryData [] */
/* @var $tableSizeData [] */

use sjaakp\gcharts\LineChart;

$this->title = 'Статистика';

?>
<div class="statistics-index">

    <div class="jumbotron">
        <h1>Статистика</h1>

        <div class="panel-group text-left" id="accordion" role="tablist">
            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="headingOne">
                    <h4 class="panel-title">
                        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            Скорость выборки статистики из БД - <?=$statisticsQueryData[ 'db' ][ 'query_time' ] ?> сек, ответов - <?=count($statisticsQueryData[ 'data' ]) ?> / <?=$statisticsQueryData[ 'pagination' ][ 'count' ] ?> (подробнее)
                        </a>
                    </h4>
                </div>
                <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                    <div class="panel-body">
                        <pre class="text-left"><?=$statisticsQueryData[ 'db' ][ 'sql' ] ?></pre>

                        <? if (!empty($statisticsQueryData[ 'data' ])) : ?>
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <? foreach (array_keys($statisticsQueryData[ 'data' ][ 0 ]) as $key) : ?>
                                        <th><?=$key ?></th>
                                    <? endforeach; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <? foreach ($statisticsQueryData[ 'data' ] as $row) : ?>
                                    <tr>
                                        <? foreach ($row as $value) : ?>
                                            <td><?=$value ?></td>
                                        <? endforeach; ?>
                                    </tr>
                                <? endforeach; ?>
                                </tbody>
                            </table>
                        <? else : ?>
                            <p class="help-block">Запрос не вернул никаких данных.</p>
                        <? endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="body-content">

        <div class="row text-center">
            <div class="col-lg-6">
                <h2>Обновление списка видео</h2>

                <?= LineChart::widget([
                    'height' => '400px',
                    'dataProvider' => $videosDataProvider,
                    'columns' => [
                        'datetime:datetime',
                        'duration'
                    ],
                    'options' => [
                        'curveType' => 'function',
                    ],
                ]) ?>
            </div>
            <div class="col-lg-6">
                <h2>Обновление статистики по видео</h2>

                <?= LineChart::widget([
                    'height' => '400px',
                    'dataProvider' => $statisticsDataProvider,
                    'columns' => [
                        'datetime:datetime',
                        'duration'
                    ],
                    'options' => [
                        'curveType' => 'function',
                    ],
                ]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <h2 class="text-center">Объем БД</h2>
                <br>

                <table class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Таблица</th>
                        <th>Данные</th>
                        <th>Индексы</th>
                        <th>Общий объем</th>
                    </tr>
                    </thead>
                    <tbody>
                    <? $fullSize = 0;?>
                    <? foreach ($tableSizeData as $table) : ?>
                        <?
                        $fullSize += $table[ 'DATA_LENGTH' ] + $table[ 'INDEX_LENGTH' ];

                        $rowClass = '';
                        if ($table[ 'DATA_LENGTH' ] + $table[ 'INDEX_LENGTH' ] >= 1024 * 1024 * 1024)
                            $rowClass = 'danger';
                        else if ($table[ 'DATA_LENGTH' ] + $table[ 'INDEX_LENGTH' ] >= 1024 * 1024)
                            $rowClass = 'warning';
                        ?>
                        <tr<? if ($rowClass != '') : ?> class="<?=$rowClass ?>"<? endif; ?>>
                            <td>
                                <?=$table[ 'TABLE_NAME' ]; ?>
                                <? if ($table[ 'TABLE_COMMENT'] != '') : ?>
                                    <span class="text-muted">(<?=$table[ 'TABLE_COMMENT' ]; ?>)</span>
                                <? endif; ?>
                            </td>
                            <td>
                                <?=Yii::$app->formatter->asShortSize($table[ 'DATA_LENGTH' ], 1) ?>
                            </td>
                            <td>
                                <?=Yii::$app->formatter->asShortSize($table[ 'INDEX_LENGTH' ], 1) ?>
                            </td>
                            <td>
                                <?=Yii::$app->formatter->asShortSize($table[ 'DATA_LENGTH' ] + $table[ 'INDEX_LENGTH' ], 1) ?>
                            </td>
                        </tr>

                    <? endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3">Итого:</td>
                        <td>
                            <?=Yii::$app->formatter->asShortSize($fullSize, 1) ?> / <?=Yii::$app->formatter->asShortSize(disk_total_space('/var/lib/mysql'), 1) ?>
                            (<b><?=Yii::$app->formatter->asPercent($fullSize / disk_total_space('/var/lib/mysql')) ?></b>)
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>
