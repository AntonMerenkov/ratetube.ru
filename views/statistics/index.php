<?php

/* @var $this yii\web\View */
/* @var $videosDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsDatesData [] */

/* @var $tableSizeData [] */

use app\components\Statistics;
use sjaakp\gcharts\LineChart;
use sjaakp\gcharts\TimelineChart;
use yii\data\ArrayDataProvider;

$this->title = 'Статистика';

?>
<div class="statistics-index">

    <div class="jumbotron">
        <h1>Статистика</h1>

        <div class="panel-group text-left" id="accordion" role="tablist">
            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="headingOne">
                    <h4 class="panel-title">
                        <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne"
                           aria-expanded="true" aria-controls="collapseOne">
                            Скорость выборки статистики из БД - <?= $statisticsQueryData['db']['query_time'] ?> сек,
                            ответов - <?= count($statisticsQueryData['data']) ?>
                            / <?= $statisticsQueryData['pagination']['count'] ?> (подробнее)
                        </a>
                    </h4>
                </div>
                <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                    <div class="panel-body">
                        <pre class="text-left"><?= $statisticsQueryData['db']['sql'] ?></pre>

                        <? if (!empty($statisticsQueryData['data'])) : ?>
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <? foreach (array_keys($statisticsQueryData['data'][0]) as $key) : ?>
                                        <th><?= $key ?></th>
                                    <? endforeach; ?>
                                </tr>
                                </thead>
                                <tbody>
                                <? foreach ($statisticsQueryData['data'] as $row) : ?>
                                    <tr>
                                        <? foreach ($row as $value) : ?>
                                            <td><?= $value ?></td>
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
                <h3 class="text-center">Наполняемость БД</h3>
                <br>

                <? foreach ($statisticsDatesData as $key => $data) : ?>
                    <div class="row">
                        <div class="col-md-1">
                            <label style="margin-top: 10px;"><?= Statistics::$timeTypes[ $key ] ?></label>
                        </div>
                        <div class="col-md-11">
                            <? $statisticsDatesDataProvider = new ArrayDataProvider([
                                'allModels' => array_map(function($item) use ($key) {
                                    return [
                                        'timeframe' => Statistics::$timeTypes[ $key ],
                                        'name' => '',
                                        'start_date' => $item[ 0 ],
                                        'end_date' => $item[ 1 ]
                                    ];
                                }, $data),
                                'pagination' => false,
                            ]); ?>

                            <?= TimelineChart::widget([
                                'height' => '100px',
                                'dataProvider' => $statisticsDatesDataProvider,
                                'columns' => [
                                    'timeframe:string',
                                    'name:string',
                                    'start_date:datetime',
                                    'end_date:datetime',
                                ],
                                'options' => [
                                    'timeline' => [
                                        'singleColor' => Statistics::$timeColors[ $key ],
                                        'showRowLabels' => false,
                                        'trigger' => 'none'
                                    ],
                                ],
                            ]) ?>
                        </div>
                    </div>
                <? endforeach; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <h2 class="text-center">Объем БД</h2>
                <br>

                <table class="table table-bordered" id="size-table">
                    <thead>
                    <tr>
                        <th>Таблица</th>
                        <th>Время</th>
                        <th>Данные</th>
                        <th>Индексы</th>
                        <th>Общий объем</th>
                    </tr>
                    </thead>
                    <tbody>
                    <? $fullSize = 0; ?>
                    <? foreach ($tableSizeData as $table) : ?>
                        <?
                        $fullSize += $table['DATA_LENGTH'] + $table['INDEX_LENGTH'];

                        $rowClass = '';
                        if ($table['DATA_LENGTH'] + $table['INDEX_LENGTH'] >= 1024 * 1024 * 1024)
                            $rowClass = 'danger';
                        else if ($table['DATA_LENGTH'] + $table['INDEX_LENGTH'] >= 1024 * 1024)
                            $rowClass = 'warning';
                        ?>
                        <tr<? if ($rowClass != '') : ?> class="<?= $rowClass ?>"<? endif; ?>>
                            <td>
                                <?= $table['TABLE_NAME']; ?>
                                <? if ($table['TABLE_COMMENT'] != '') : ?>
                                    <span class="text-muted">(<?= $table['TABLE_COMMENT']; ?>)</span>
                                <? endif; ?>
                            </td>
                            <td>
                                <? if (array_key_exists('MIN_DATE', $table)) : ?>
                                    <? if (is_null($table['MIN_DATE'])) : ?>
                                        <span class="text-muted">нет данных</span>
                                    <? else : ?>
                                        <? if ($table['DATE_DIFF'] >= Statistics::$timeDiffs[preg_replace('/^statistics_/', '', $table['TABLE_NAME'])]) : ?>
                                            <i class="text-success glyphicon glyphicon-ok"></i>
                                        <? else : ?>
                                            <i class="text-danger glyphicon glyphicon-remove"></i>
                                        <? endif; ?>
                                        <span class="text-muted" style="font-size: 80%">
                                            <?= Yii::$app->formatter->asDuration($table['DATE_DIFF'] > 86400 ? floor($table['DATE_DIFF'] / 3600) * 3600 : floor($table['DATE_DIFF'] / 60) * 60) ?>
                                        </span>
                                    <? endif; ?>
                                <? endif; ?>
                            </td>
                            <td>
                                <?= Yii::$app->formatter->asShortSize($table['DATA_LENGTH'], 1) ?>
                            </td>
                            <td>
                                <?= Yii::$app->formatter->asShortSize($table['INDEX_LENGTH'], 1) ?>
                            </td>
                            <td>
                                <?= Yii::$app->formatter->asShortSize($table['DATA_LENGTH'] + $table['INDEX_LENGTH'], 1) ?>
                            </td>
                        </tr>
                    <? endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="4">Итого:</td>
                        <td>
                            <?= Yii::$app->formatter->asShortSize($fullSize, 1) ?>
                            / <?= Yii::$app->formatter->asShortSize(disk_total_space('/var/lib/mysql'), 1) ?>
                            (<b><?= Yii::$app->formatter->asPercent($fullSize / disk_total_space('/var/lib/mysql')) ?></b>)
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>
