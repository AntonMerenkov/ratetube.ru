<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * jQuery circle progress.
 */
class CircleProgressAsset extends AssetBundle
{
    public $sourcePath = '@vendor/thatsus/jquery-circle-progress/dist';
    public $js = [
        'circle-progress.js',
    ];
    public $jsOptions = [
        'position' => View::POS_HEAD
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'app\assets\JqueryEasingAsset',
    ];
}
