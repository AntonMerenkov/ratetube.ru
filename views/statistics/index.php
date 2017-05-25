<?php

/* @var $this yii\web\View */
/* @var $videosDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsQueryData [] */

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

    </div>
</div>
