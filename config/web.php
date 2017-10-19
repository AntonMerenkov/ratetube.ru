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
                'admin/logreader' => 'logreader/default/index',
                'admin/logreader/<action>' => 'logreader/default/<action>',
                'ajax/get-statistics/category/<category_id:[\d\w-_]+>' => 'site/ajax-get-statistics',
                'ajax/get-statistics/channel/<channel_id:[\d\w-_]+>' => 'site/ajax-get-statistics',
                'ajax/get-statistics' => 'site/ajax-get-statistics',
                'ajax/set-time/<id>' => 'site/ajax-set-time',
                'ajax/set-sorting/<id>' => 'site/ajax-set-sorting',
                'file/<uuid>/<no_stat>' => 'ads/file',
                'file/<uuid>' => 'ads/file',
                '' => 'site/index',
                '<action:error>' => 'site/<action>',
                'admin/<action:logout>' => 'site/<action>',
                'admin' => 'site/login',
                'admin/<controller>' => '<controller>/index',
                'admin/<controller>/<action>' => '<controller>/<action>',
                'admin/<controller>/<action>/<id:\d+>' => '<controller>/<action>',
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
                'Основной журнал' => '@app/runtime/logs/app.log',
                'Highload-запросы' => '@app/runtime/logs/highload.log',
                'Использование API-ключей' => '@app/runtime/logs/api-keys.log',
                'Выполнение агентов' => '@app/runtime/logs/agent.log',
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
            'allowedIPs' => ['178.210.51.10', '10.0.2.2', '83.139.*.*'],
        ];

        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => 'yii\gii\Module',
            'allowedIPs' => ['178.210.51.10', '10.0.2.2', '83.139.*.*'],
        ];
    }
}

return $config;
