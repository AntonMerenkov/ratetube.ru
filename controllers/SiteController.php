<?php

namespace app\controllers;

use app\components\HighloadAPI;
use app\components\YoutubeAPI;
use app\models\Categories;
use app\components\Statistics;
use app\models\Channels;
use app\models\Positions;
use app\models\PositionStatistics;
use app\models\StatisticsMinute;
use app\models\Videos;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
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
            'query' => $query,
        ]);

        // подсчет статистики по позициям видео
        $positionIds = array_map(function($item) {
            return $item[ 'id' ];
        }, array_filter($statisticsQueryData[ 'data' ], function($item) {
            return $item[ 'ad' ];
        }));

        $positionIds = ArrayHelper::map(Positions::find()->where([
            'video_id' => $positionIds
        ])->all(), 'id', 'id');

        if (!empty($positionIds)) {
            $positionStatistics = ArrayHelper::map(PositionStatistics::find()->where([
                'position_id' => $positionIds,
                'date' => date('Y-m-d')
            ])->all(), 'position_id', function($item) {
                return $item;
            });

            foreach ($positionIds as $positionId)
                if (!isset($positionStatistics[ $positionId ])) {
                    $positionStatistics[ $positionId ] = new PositionStatistics();
                    $positionStatistics[ $positionId ]->position_id = $positionId;
                    $positionStatistics[ $positionId ]->date = date('Y-m-d');
                    $positionStatistics[ $positionId ]->views = 0;
                    $positionStatistics[ $positionId ]->save();
                }

            foreach ($positionStatistics as $model) {
                //$model->views++;
                //$model->save();
            }

            PositionStatistics::updateAllCounters([
                'views' => 1,
            ], [
                'id' => ArrayHelper::map($positionStatistics, 'id', 'id')
            ]);
        }

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
            'query' => $query,
        ]);

        // рандомизация данных для анимации
        $lastTime = strtotime($statisticsQueryData[ 'time' ][ 'from' ]);
        $prevTime = strtotime($statisticsQueryData[ 'time' ][ 'to' ]);
        if ($lastTime > $prevTime) {
            $timeDiff = time() - $lastTime;

            // 30% от максимально возможного прироста
            foreach ($statisticsQueryData[ 'data' ] as $id => $value) {
                $statisticsQueryData[ 'data' ][ $id ][ 'views' ] += round($statisticsQueryData[ 'data' ][ $id ][ 'views_diff' ] * $timeDiff / ($lastTime - $prevTime) * 0.3);

                if (mt_rand(0, 1) == 1) {
                    $viewsRand = mt_rand(0, abs(floor($statisticsQueryData[ 'data' ][ $id ][ 'views_diff' ] * 0.1)));
                    $statisticsQueryData[ 'data' ][ $id ][ 'views_diff' ] += $viewsRand;
                    $statisticsQueryData[ 'data' ][ $id ][ 'views' ] += $viewsRand;
                }
                if (mt_rand(0, 1) == 1)
                    $statisticsQueryData[ 'data' ][ $id ][ 'likes_diff' ] += mt_rand(0, abs(floor($statisticsQueryData[ 'data' ][ $id ][ 'likes_diff' ] * 0.1)));
                if (mt_rand(0, 1) == 1)
                    $statisticsQueryData[ 'data' ][ $id ][ 'dislikes_diff' ] += mt_rand(0, abs(floor($statisticsQueryData[ 'data' ][ $id ][ 'dislikes_diff' ] * 0.1)));
                if (mt_rand(0, 1) == 1)
                    $statisticsQueryData[ 'data' ][ $id ][ 'position_diff' ] += mt_rand(-abs(floor($statisticsQueryData[ 'data' ][ $id ][ 'position_diff' ] * 0.1)),
                        abs(floor($statisticsQueryData[ 'data' ][ $id ][ 'position_diff' ] * 0.1)));
            }
        }

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

        $streamingData = Statistics::getStreaming();
        //$streamingData = [];

        return Json::encode([
            'data' => $statisticsQueryData[ 'data' ],
            'streaming' => $streamingData,
        ]);
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

        return $this->redirect(Yii::$app->request->referrer ? Yii::$app->request->referrer : '/');
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

        return $this->redirect(Yii::$app->request->referrer ? Yii::$app->request->referrer : '/');
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

    public function actionTest()
    {
        die();
        $videoIds = ArrayHelper::map(Videos::find()->active()->all(), 'id', 'video_link');

        $time = microtime(true);
        echo '<h3>Обычный</h3>';
        $response = YoutubeAPI::query('videos', ['id' => $videoIds], ['statistics', 'liveStreamingDetails'], YoutubeAPI::QUERY_MULTIPLE);
        ?><pre><?print_r(microtime(true) - $time)?> сек.</pre><?

        $time = microtime(true);
        echo '<h3>Highload</h3>';
        $response = HighloadAPI::query('videos', ['id' => $videoIds], ['statistics', 'liveStreamingDetails'], YoutubeAPI::QUERY_MULTIPLE);
        ?><pre><?print_r(microtime(true) - $time)?> сек.</pre><?
    }
}
