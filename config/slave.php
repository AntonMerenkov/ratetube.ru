<?php

$config = [
    'id' => 'ratetube-slave',
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
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
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
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                '' => 'query/index',
                '<action>' => 'query/<action>',
            ],
        ],
        'curl' => 'app\components\Curl',
    ],
    'on beforeAction' => function ($event) {
        if ($event->action->controller->id != 'query') {
            $event->isValid = false;
            $event->action->controller->redirect(['query/index']);
        }
    },
];

return $config;
