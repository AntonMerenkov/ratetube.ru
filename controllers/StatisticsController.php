<?php

namespace app\controllers;

use app\models\Categories;
use app\models\Profiling;
use app\components\Statistics;
use Yii;
use app\models\Channels;
use app\models\ChannelsSearch;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * StatisticsController class.
 */
class StatisticsController extends Controller
{
    public $layout = 'admin';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Channels models.
     * @return mixed
     */
    public function actionIndex()
    {
        $videosData = Profiling::find()->where([
            'code' => 'agent-update-videos'
        ])->all();

        $videosData = array_map(function($item) {
            return $item[ 0 ];
        }, array_chunk($videosData, floor(count($videosData) / 1000)));

        $videosDataProvider = new ActiveDataProvider([
            'models' => $videosData,
            'pagination' => false
        ]);

        $statisticsData = Profiling::find()->where([
            'code' => 'agent-update-statistics'
        ])->all();

        $statisticsData = array_map(function($item) {
            return $item[ 0 ];
        }, array_chunk($statisticsData, floor(count($statisticsData) / 1000)));

        $statisticsDataProvider = new ActiveDataProvider([
            'models' => $statisticsData,
            'pagination' => false
        ]);

        $statisticsQueryData = Statistics::getStatistics();

        $tableSizeData = Statistics::getTableSizeData();

        return $this->render('index', [
            'videosDataProvider' => $videosDataProvider,
            'statisticsDataProvider' => $statisticsDataProvider,
            'statisticsQueryData' => $statisticsQueryData,
            'tableSizeData' => $tableSizeData,
        ]);
    }
}
