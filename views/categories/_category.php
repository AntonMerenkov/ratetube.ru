<?php
/* @var $model \app\models\Categories */

use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

?>

<?=Html::a(
    $model->name,
    \yii\helpers\Url::toRoute(['index', 'id' => $model->id]),
    [
        'class' => 'list-group-item' . (Yii::$app->request->getQueryParam('id') == $model->id ? ' active' : '')
    ]
)?>