<?php
/**
 * @var \yii\web\View $this
 * @var \yii\data\ArrayDataProvider $dataProvider
 */

use yii\grid\GridView;
use yii\helpers\Html;
use zhuravljov\yii\logreader\Log;

$this->title = 'Журналы';
$this->params['breadcrumbs'][] = 'Журналы';
?>

<h1><?=$this->title ?></h1>

<div class="logreader-index">
    <?= GridView::widget([
        'layout' => '{items}',
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'attribute' => 'name',
                'header' => 'Журнал',
                'format' => 'raw',
                'value' => function (Log $log) {
                    return Html::tag('h5', join("\n", [
                        Html::encode($log->name),
                        '<br/>',
                        Html::tag('small', Html::encode($log->fileName)),
                    ]));
                },
            ],
            [
                'attribute' => 'counts',
                'header' => 'Записей',
                'format' => 'raw',
                'headerOptions' => ['class' => 'sort-ordinal'],
                'value' => function (Log $log) {
                    return $this->render('_counts', ['log' => $log]);
                },
            ],
            [
                'attribute' => 'size',
                'header' => 'Объем',
                'format' => 'shortSize',
                'headerOptions' => ['class' => 'sort-ordinal'],
            ],
            [
                'attribute' => 'updatedAt',
                'header' => 'Время обновления',
                'format' => 'relativeTime',
                'headerOptions' => ['class' => 'sort-numerical'],
            ],
            [
                'class' => '\yii\grid\ActionColumn',
                'urlCreator' => function ($action, Log $log) {
                    return [$action, 'slug' => $log->slug];
                },
                'template' => '<div class="btn-group btn-group-justified">{view}{history}{archive}</div>',
                'buttons' => [
                    'history' => function ($url) {
                        return Html::a('<i class="glyphicon glyphicon-folder-open"></i>', $url, [
                            'class' => 'btn btn-success',
                        ]);
                    },
                    'view' => function ($url, Log $log) {
                        return !$log->isExist ? '' : Html::a('<i class="glyphicon glyphicon-eye-open"></i>', $url, [
                            'class' => 'btn btn-primary',
                            'target' => '_blank',
                        ]);
                    },
                    'archive' => function ($url, Log $log) {
                        return !$log->isExist ? '' : Html::a('<i class="glyphicon glyphicon-trash"></i>', $url, [
                            'class' => 'btn btn-danger',
                            'data' => ['method' => 'post', 'confirm' => 'Вы действительно хотите переместить журнал в архив?'],
                        ]);
                    },
                ],
                'options' => [
                    'style' => 'width: 150px;'
                ]
            ],
        ],
    ]) ?>
</div>
<?php
$this->registerCss(<<<CSS

.logreader-index .table tbody td {
    vertical-align: middle;
}

CSS
);