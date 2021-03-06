<?php

namespace app\controllers;
use app\components\YoutubeAPI;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

/**
 * Контроллер для работы в качестве slave-сервера.
 *
 * Class QueryController
 * @package app\controllers
 */
class QueryController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            return \Yii::$app->id == 'ratetube-slave';
                        }
                    ],
                ],
            ],
        ];
    }

    /**
     * Запрос к YouTube Data API.
     * @return string
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        $time = microtime(true);

        ini_set('memory_limit', '1024M');

        // валидация ключа авторизации
        if (!isset($_POST[ 'key' ]) || $_POST[ 'key' ] != \Yii::$app->request->cookieValidationKey)
            throw new ForbiddenHttpException('Нет доступа.');

        // загрузка переданных API-ключей
        YoutubeAPI::$keys = array_map(function($item) {
            $item[ 'quota_diff' ] = 0;
            return $item;
        }, $_POST[ 'apiKeys' ]);

        // выполнение запроса
        $result = YoutubeAPI::query($_POST[ 'method' ], $_POST[ 'params' ], $_POST[ 'parts' ], $_POST[ 'type' ]);

        // возвращение результата и данных о расходе квоты
        $data = [
            'ip' => $_SERVER[ 'SERVER_ADDR'],
            'time' => round(microtime(true) - $time, 2),
            'result' => gzcompress(serialize($result)),
            'keys' => YoutubeAPI::$keys
        ];

        return serialize($data);
    }

    /**
     * Проверка соединения.
     *
     * @param null $key
     * @return string
     */
    public function actionTest($key = null)
    {
        return Json::encode([
            'status' => ($key == \Yii::$app->request->cookieValidationKey ? 1 : 0)
        ]);
    }
}
