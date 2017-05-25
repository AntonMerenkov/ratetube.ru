<?php

namespace app\controllers;

use app\models\Categories;
use app\models\Statistics;
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
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex($id = null, $page = 1)
    {
        $categoryId = null;
        if (!is_null($id))
            $categoryId = Categories::findOne(['code' => $id])->id;

        $statisticsQueryData = Statistics::getStatistics($page, ['category_id' => $categoryId]);

        return $this->render('index', [
            'statisticsQueryData' => $statisticsQueryData
        ]);
    }

    /**
     * Получение статистики (AJAX).
     *
     * @param null $id
     * @param int $page
     * @return string
     */
    public function actionAjaxGetStatistics($id = null, $page = 1)
    {
        $categoryId = null;
        if (!is_null($id))
            $categoryId = Categories::findOne(['code' => $id])->id;

        $statisticsQueryData = Statistics::getStatistics($page, ['category_id' => $categoryId]);

        return Json::encode($statisticsQueryData[ 'data' ]);
    }

    /**
     * Получение статистики (AJAX).
     *
     * @param null $id
     * @return string
     */
    public function actionAjaxSetTime($id)
    {
        if (isset(Statistics::$timeTypes[ $id ]))
            Yii::$app->session->set(Statistics::SESSION_KEY, $id);

        return $this->redirect(Yii::$app->request->referrer);
    }

    /**
     * Вход в административную панель.
     *
     * @return string
     */
    public function actionLogin()
    {
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
