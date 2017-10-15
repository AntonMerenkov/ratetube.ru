<?php

namespace app\controllers;
use yii\filters\AccessControl;
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
                'denyCallback' => function ($rule, $action) {
                    if (\Yii::$app->id != 'ratetube-slave')
                        throw new ForbiddenHttpException('Нет доступа.');
                }
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
        echo 'YouTube API';
    }

    /**
     * Проверка соединения.
     *
     * @return string
     */
    public function actionTest()
    {
        echo 'test';
    }

}
