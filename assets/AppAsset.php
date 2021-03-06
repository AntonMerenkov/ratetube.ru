<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'lib/flexslider/flexslider.css',
        'lib/jqcloud/jqcloud.min.css',
        'lib/modal-video/css/modal-video.min.css',
        'lib/jquery-modal/jquery.modal.min.css',
        'lib/font-awesome/css/font-awesome.min.css',
        'css/site.css',
    ];
    public $js = [
        'lib/jqueryui/jquery-ui.min.js',
        'lib/flexslider/jquery.flexslider-min.js',
        'lib/jquery-circle-progress/circle-progress.js',
        'lib/share42/share42.js',
        'lib/visibility/visibility.min.js',
        'lib/jqcloud/jqcloud.min.js',
        'lib/modal-video/js/jquery-modal-video.js',
        'lib/jquery-modal/jquery.modal.min.js',
        'lib/jquery-migrate/jquery-migrate-3.0.0.min.js',
        'js/scripts.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
    public $jsOptions = [
        'position' => View::POS_HEAD
    ];
}
