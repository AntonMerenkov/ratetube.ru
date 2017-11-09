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
use app\widgets\Streaming;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\validators\IpValidator;
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
            'ip' => [
                'class' => AccessControl::className(),
                'only' => ['login'],
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) {
                            $validator = new IpValidator([
                                'ranges' => Yii::$app->params[ 'adminIP' ]
                            ]);

                            return $validator->validate(Yii::$app->request->userIP);
                        },
                    ],
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
     * Накрутка статистики.
     *
     * @param $data
     */
    private function cheatData($data)
    {
        // рандомизация данных для анимации
        $lastTime = strtotime($data[ 'time' ][ 'from' ]);
        $prevTime = strtotime($data[ 'time' ][ 'to' ]);
        if ($lastTime > $prevTime) {
            $timeDiff = time() - $lastTime;
            $timeDiffPercent = $timeDiff / ($lastTime - $prevTime);

            // 30% от максимально возможного прироста
            foreach ($data[ 'data' ] as $id => $value) {
                $data[ 'data' ][ $id ][ 'views' ] += round($data[ 'data' ][ $id ][ 'views_diff' ] * 0.3 * $timeDiffPercent);

                $viewsRand = floor($data[ 'data' ][ $id ][ 'views_diff' ] * 0.1 * $timeDiffPercent);
                $data[ 'data' ][ $id ][ 'views_diff' ] += $viewsRand;
                $data[ 'data' ][ $id ][ 'views' ] += $viewsRand;

                $data[ 'data' ][ $id ][ 'likes_diff' ] += floor($data[ 'data' ][ $id ][ 'likes_diff' ] * 0.1 * $timeDiffPercent);
                $data[ 'data' ][ $id ][ 'dislikes_diff' ] += floor($data[ 'data' ][ $id ][ 'dislikes_diff' ] * 0.1 * $timeDiffPercent);
                //$data[ 'data' ][ $id ][ 'position_diff' ] += floor($data[ 'data' ][ $id ][ 'position_diff' ] * 0.1 * $timeDiffPercent);
            }
        }

        return $data;
    }

    /**
     * Накрутка позиций.
     *
     * @param $data
     */
    private function cheatDataPositions($data)
    {
        $count = 2;

        if (count($data[ 'data' ]) < 5)
            $count = 0;

        if ($count > 0) {
            $keys = array_rand($data[ 'data' ], $count * 2);
            shuffle($keys);

            for ($i = 0; $i < $count; $i++) {
                $tmp = $data[ 'data' ][ $keys[ $i * 2 ] ];
                $data[ 'data' ][ $keys[ $i * 2 ] ] = $data[ 'data' ][ $keys[ $i * 2 + 1 ] ];
                $data[ 'data' ][ $keys[ $i * 2 + 1 ] ] = $tmp;
            }
        }

        return $data;
    }

    /**
     * Кеширование для экономии памяти - сохраняем 1 страницу.
     *
     * @param null $category_id
     * @param null $channel_id
     * @param null $query
     * @param int $page
     * @internal param $params
     * @return mixed
     */
    private function getCachedStatistics($category_id = null, $channel_id = null, $query = null, $page = 1)
    {
        $idArray = [];

        $idArray[] = 't' . Yii::$app->session->get(Statistics::TIME_SESSION_KEY, Statistics::QUERY_TIME_HOUR);
        $idArray[] = 's' . Yii::$app->session->get(Statistics::SORT_SESSION_KEY, Statistics::SORT_TYPE_VIEWS_DIFF);

        if (!is_null($page))
            $idArray[] = 'p' . $page;
        if (!is_null($category_id))
            $idArray[] = 'cat' . $category_id;
        if (!is_null($channel_id))
            $idArray[] = 'ch' . $channel_id;
        if (!is_null($query))
            $idArray[] = 'q' . $query;

        $cacheId = 'index-statistics-' . implode('-', $idArray);

        Yii::$app->cache->delete($cacheId); // for debug
        $data = Yii::$app->cache->getOrSet($cacheId, function() use ($category_id, $channel_id, $query, $page) {
            Yii::beginProfile('Вычисление статистики');
            if (!is_null($category_id))
                $category_id = Categories::findOne(['code' => $category_id])->id;

            if (!is_null($channel_id))
                $channel_id = Channels::findOne(['id' => $channel_id])->id;

            $data = Statistics::getStatistics($page, [
                'category_id' => $category_id,
                'channel_id' => $channel_id,
                'query' => $query,
                'findCached' => true,
                //'noCache' => true, // for debug
            ]);

            Yii::endProfile('Вычисление статистики');

            return $data;
        }, 300);

        $data = Yii::$app->cache->getOrSet($cacheId . '-cheat', function() use ($data, $cacheId) {
            $data = Yii::$app->cache->getOrSet($cacheId . '-cheat-positions', function() use ($data) {
                return $this->cheatDataPositions($data);
            }, 20);

            return $this->cheatData($data);
        }, 10);

        return $data;
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
        Yii::beginProfile('Загрузка статистики');
        $statisticsQueryData = $this->getCachedStatistics($category_id, $channel_id, $query, $page);
        Yii::endProfile('Загрузка статистики');

        Yii::beginProfile('Загрузка позиций видео');

        // подсчет статистики по позициям видео
        $positionIds = array_map(function($item) {
            return $item[ 'id' ];
        }, array_filter($statisticsQueryData[ 'data' ], function($item) {
            return $item[ 'ad' ];
        }));

        $positionIds = ArrayHelper::map(Positions::find()->where([
            'video_id' => $positionIds
        ])->all(), 'id', 'id');

        Yii::endProfile('Загрузка позиций видео');

        if (!empty($positionIds)) {
            Yii::beginProfile('Сохранение позиций видео');

            $transaction = Yii::$app->db->beginTransaction();

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

            PositionStatistics::updateAllCounters([
                'views' => 1,
            ], [
                'id' => ArrayHelper::map($positionStatistics, 'id', 'id')
            ]);

            $transaction->commit();

            Yii::endProfile('Сохранение позиций видео');
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
        $statisticsQueryData = $this->getCachedStatistics($category_id, $channel_id, $query, $page);

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

        $streamingData = Yii::$app->cache->get(Streaming::CACHE_KEY);
        $streamingData = array_slice($streamingData, 0, Streaming::$count);

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
}
