<?php

namespace app\controllers;

use app\models\Categories;
use app\components\Statistics;
use app\models\Channels;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Список видео со статистикой.
     *
     * PS: При внесении дополнительного фильтра изменить self::actionAjaxGetStatistics и #refreshButton.
     *
     * @param null $category_id
     * @param null $channel_id
     * @param null $query
     * @param int $page
     * @return string
     */
    public function actionIndex($category_id = null, $channel_id = null, $query = null, $page = 1)
    {
        if (!is_null($category_id))
            $category_id = Categories::findOne(['code' => $category_id])->id;

        if (!is_null($channel_id))
            $channel_id = Channels::findOne(['id' => $channel_id])->id;

        $statisticsQueryData = Statistics::getStatistics($page, [
            'category_id' => $category_id,
            'channel_id' => $channel_id,
        ]);

        return $this->render('index', [
            'statisticsQueryData' => $statisticsQueryData
        ]);
    }

    /**
     * Получение статистики (AJAX).
     *
     * @param null $category_id
     * @param null $channel_id
     * @param null $query
     * @param int $page
     * @return string
     * @internal param null $id
     */
    public function actionAjaxGetStatistics($category_id = null, $channel_id = null, $query = null, $page = 1)
    {
        if (!is_null($category_id))
            $category_id = Categories::findOne(['code' => $category_id])->id;

        if (!is_null($channel_id))
            $channel_id = Channels::findOne(['id' => $channel_id])->id;

        $statisticsQueryData = Statistics::getStatistics($page, [
            'category_id' => $category_id,
            'channel_id' => $channel_id,
        ]);

        // для демо
        /*for ($i = 1; $i <= rand(2, 4); $i++) {
            $j = rand(0, count($statisticsQueryData[ 'data' ]) - 1);
            $k = rand(0, count($statisticsQueryData[ 'data' ]) - 1);

            if ($j != $k) {
                $tmp = $statisticsQueryData[ 'data' ][ $j ];
                $statisticsQueryData[ 'data' ][ $j ] = $statisticsQueryData[ 'data' ][ $k ];
                $statisticsQueryData[ 'data' ][ $k ] = $tmp;
            }
        }*/

        return Json::encode($statisticsQueryData[ 'data' ]);
    }

    /**
     * Установка времени для вычисления разницы (AJAX).
     *
     * @param null $id
     * @return string
     */
    public function actionAjaxSetTime($id)
    {
        if (isset(Statistics::$timeTypes[ $id ]))
            Yii::$app->session->set(Statistics::TIME_SESSION_KEY, $id);

        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * Установка типа сортировки (AJAX).
     *
     * @param null $id
     * @return string
     */
    public function actionAjaxSetSorting($id)
    {
        if (isset(Statistics::$sortingTypes[ $id ]))
            Yii::$app->session->set(Statistics::SORT_SESSION_KEY, $id);

        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * Вход в административную панель.
     *
     * @return string
     */
    public function actionLogin()
    {
        $this->layout = 'admin';

        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/statistics/index']);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['/statistics/index']);
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Выход из административной панели.
     *
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
