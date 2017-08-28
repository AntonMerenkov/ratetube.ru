<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'ratetube',
    'basePath' => dirname(__DIR__),
    'language' => 'ru',
    'sourceLanguage' => 'ru',
    'bootstrap' => ['log'],
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
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                'category/<category_id:[\d\w-_]+>' => 'site/index',
                'channel/<channel_id:[\d\w-_]+>' => 'site/index',
                'ajax/get-statistics/category/<category_id:[\d\w-_]+>' => 'site/ajax-get-statistics',
                'ajax/get-statistics/channel/<channel_id:[\d\w-_]+>' => 'site/ajax-get-statistics',
                'ajax/get-statistics' => 'site/ajax-get-statistics',
                'ajax/set-time/<id>' => 'site/ajax-set-time',
                'ajax/set-sorting/<id>' => 'site/ajax-set-sorting',
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
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    session_start();
    if ($_SESSION[ '__id' ] > 0) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['*'],
        ];

        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => 'yii\gii\Module',
            'allowedIPs' => ['*'],
        ];
    }
}

return $config;
