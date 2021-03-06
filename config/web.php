<?php

use app\components\HighloadAPI;
use app\components\YoutubeAPI;

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'ratetube',
    'basePath' => dirname(__DIR__),
    'language' => 'ru',
    'sourceLanguage' => 'ru',
    'bootstrap' => [
        'log',
        'logreader'
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'XLVFEHM1hG6t52DMKUeBUJkVDDDXigN0',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            //'traceLevel' => YII_DEBUG ? 3 : 0,
            'traceLevel' => 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['api-keys'],
                    'logFile' =>  '@runtime/logs/api-keys.log',
                    'logVars' => [],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['highload'],
                    'logFile' =>  '@runtime/logs/highload.log',
                    'logVars' => [],
                ],
            ],
        ],
        'sphinx' => [
            'class' => 'yii\sphinx\Connection',
            'dsn' => 'mysql:host=127.0.0.1;port=9306;',
            'username' => 'root',
            'password' => '',
        ],
        'db' => require(__DIR__ . '/db.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                'category/<category_id:[\d\w-_]+>' => 'site/index',
                'channel/<channel_id:[\d\w-_]+>' => 'site/index',
                '<id:(partnership)>' => 'site/page',
                'rt--admin/logreader' => 'logreader/default/index',
                'rt--admin/logreader/<action>' => 'logreader/default/<action>',
                'ajax/get-statistics/category/<category_id:[\d\w-_]+>' => 'site/ajax-get-statistics',
                'ajax/get-statistics/channel/<channel_id:[\d\w-_]+>' => 'site/ajax-get-statistics',
                'ajax/get-statistics' => 'site/ajax-get-statistics',
                'ajax/set-time/<id>' => 'site/ajax-set-time',
                'ajax/set-sorting/<id>' => 'site/ajax-set-sorting',
                'file/<uuid>/<no_stat>' => 'ads/file',
                'file/<uuid>' => 'ads/file',
                '' => 'site/index',
                '<action:error>' => 'site/<action>',
                'rt--admin/<action:logout>' => 'site/<action>',
                'rt--admin' => 'site/login',
                'rt--admin/<controller>' => '<controller>/index',
                'rt--admin/<controller>/<action>' => '<controller>/<action>',
                'rt--admin/<controller>/<action>/<id:\d+>' => '<controller>/<action>',
            ],
        ],
        'curl' => 'app\components\Curl',
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@zhuravljov/yii/logreader/views' => '@app/views_logreader'
                ],
            ],
        ],
        'session' => [
            'class' => 'yii\web\DbSession',
        ],
        'reCaptcha' => [
            'name' => 'reCaptcha',
            'class' => 'himiklab\yii2\recaptcha\ReCaptcha',
            'siteKey' => '6LfLtzcUAAAAALHyXhNd-8zfHKe4-zuqjv_egsAj',
            'secret' => '6LfLtzcUAAAAAP0IdMnnLQj9pwGdmQtA3mQjbnsE',
        ],
    ],
    'modules' => [
        'logreader' => [
            'class' => 'zhuravljov\yii\logreader\Module',
            'controllerMap' => [
                'default' => [
                    'class' => 'app\controllers\LogsController',
                ],
            ],
            'aliases' => [
                '???????????????? ????????????' => '@app/runtime/logs/app.log',
                'Highload-??????????????' => '@app/runtime/logs/highload.log',
                '?????????????????????????? API-????????????' => '@app/runtime/logs/api-keys.log',
                '???????????????????? ??????????????' => '@app/runtime/logs/agent.log',
            ],
        ],
    ],
    'params' => $params,
    'on afterAction' => function () {
        YoutubeAPI::saveData();
        HighloadAPI::saveData();
    },
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    session_start();
    if ($_SESSION[ '__id' ] > 0) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['178.210.51.10', '10.0.2.2', '83.139.*.*', '37.29.107.70'],
        ];

        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => 'yii\gii\Module',
            'allowedIPs' => ['178.210.51.10', '10.0.2.2', '83.139.*.*', '37.29.107.70'],
        ];
    }
}

return $config;
