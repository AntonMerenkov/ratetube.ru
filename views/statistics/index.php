<?php

/* @var $this yii\web\View */
/* @var $videosDataProvider yii\data\ActiveDataProvider */
/* @var $statisticsDataProvider yii\data\ActiveDataProvider */
/* @var $sql string */
/* @var $sqlTime float */

use sjaakp\gcharts\LineChart;

$this->title = 'Статистика';

?>
<div class="statistics-index">

    <div class="jumbotron">
        <h1>Статистика</h1>

        <h3>Скорость выборки статистики из БД - <?=Yii::$app->formatter->asDecimal($sqlTime, 2)?> сек</h3>
        <pre class="text-left"><?=$sql?></pre>
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
