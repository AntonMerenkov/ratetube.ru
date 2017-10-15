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
        // валидация ключа авторизации
        if (!isset($_POST[ 'key' ]) || $_POST[ 'key' ] != \Yii::$app->request->cookieValidationKey)
            throw new ForbiddenHttpException('Нет доступа.');

        // загрузка переданных API-ключей
        YoutubeAPI::$keys = $_POST[ 'apiKeys' ];

        // выполнение запроса
        $result = YoutubeAPI::query($_POST[ 'method' ], $_POST[ 'params' ], $_POST[ 'parts' ], $_POST[ 'type' ]);

        // возвращение результата и данных о расходе квоты
        return Json::encode([
            'ip' => $_SERVER[ 'SERVER_ADDR'],
            'result' => $result,
            'keys' => YoutubeAPI::$keys
        ]);
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
