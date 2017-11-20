<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Защита по IP-адресу';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="security-ip-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php Pjax::begin(); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'ip',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '<div class="btn-group btn-group-justified">{update}{delete}</div>',
                'buttons' => [
                    'update' => function ($url, $model, $key) {
                        return Html::a('<i class="glyphicon glyphicon-pencil"></i>', $url, [
                            'class' => 'btn btn-success'
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<i class="glyphicon glyphicon-trash"></i>', $url, [
                            'class' => 'btn btn-danger',
                            'data-pjax' => 1,
                            'data-method' => 'post',
                            'data-confirm' => 'Вы уверены, что хотите удалить этот элемент?'
                        ]);
                    },
                ],
                'options' => [
                    'style' => 'width: 100px;'
                ]
            ],
        ],
    ]); ?>
    <?php Pjax::end(); ?>

    <?= Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
</div>
