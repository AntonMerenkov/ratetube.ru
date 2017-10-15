<?php

namespace app\controllers;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\web\ForbiddenHttpException;

/**
 * Контроллер для работы в качестве slave-сервера.
 *
 * Class QueryController
 * @package app\controllers
 */
class QueryController extends \yii\web\Controller
{
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
     *
     * @return string
     */
    public function actionIndex()
    {
        // валидация ключа авторизации

        // загрузка переданных API-ключей

        // выполнение запроса

        // возвращение данных о расходе квоты
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
