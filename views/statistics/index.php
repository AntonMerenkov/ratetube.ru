<?php

/* @var $this yii\web\View */
/* @var $videosDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsDataProvider yii\data\ActiveDataProvider */

use sjaakp\gcharts\LineChart;

$this->title = 'Статистика';
?>
<div class="statistics-index">

    <div class="jumbotron">
        <h1>Статистика</h1>
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
