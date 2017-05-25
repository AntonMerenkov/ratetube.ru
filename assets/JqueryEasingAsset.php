<?php

namespace app\assets;

use yii\web\AssetBundle;

class JqueryEasingAsset extends AssetBundle
{
    public $sourcePath = '@bower/jquery.easing/js';
    public $js = [
        'http://code.jquery.com/ui/1.12.1/jquery-ui.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
