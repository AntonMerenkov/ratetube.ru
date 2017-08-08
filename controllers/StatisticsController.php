<?php

namespace app\controllers;

use app\models\Categories;
use app\models\Profiling;
use app\components\Statistics;
use Yii;
use app\models\Channels;
use app\models\ChannelsSearch;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
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

        $statisticsDatesData = array_map(function($item) {
            $tableModel = '\\app\\models\\' . $item;
            return Yii::$app->db->createCommand('SELECT DISTINCT(datetime) FROM ' . $tableModel::tableName())->queryColumn();
        }, Statistics::$tableModels);

        foreach ($statisticsDatesData as $key => $data) {
            $intervals = [];
            $startDate = null;
            $endDate = null;
            foreach ($data as $date) {
                if (is_null($startDate)) {
                    $startDate = $date;
                } else if (is_null($endDate)) {
                    if (strtotime($date) - strtotime($startDate) - 300 <= Statistics::$appendInterval[ $key ]) {
                        $endDate = $date;
                    } else {
                        $intervals[] = [
                            $startDate,
                            date('Y-m-d H:i:s', strtotime($startDate) + 60),
                        ];

                        $startDate = $date;
                    }
                } else {
                    if (strtotime($date) - strtotime($endDate) - 300 <= Statistics::$appendInterval[ $key ]) {
                        $endDate = $date;
                    } else {
                        $intervals[] = [
                            $startDate,
                            $endDate
                        ];

                        $startDate = $date;
                        $endDate = null;
                    }
                }
            }

            if (!is_null($startDate)) {
                if (!is_null($endDate))
                    $intervals[] = [
                        $startDate,
                        $endDate
                    ];
                else
                    $intervals[] = [
                        $startDate,
                        date('Y-m-d H:i:s', strtotime($startDate) + 60)
                    ];
            }

            $statisticsDatesData[ $key ] = $intervals;
        }

        return $this->render('index', [
            'videosDataProvider' => $videosDataProvider,
            'statisticsDataProvider' => $statisticsDataProvider,
            'statisticsQueryData' => $statisticsQueryData,
            'statisticsDatesData' => $statisticsDatesData,
            'tableSizeData' => $tableSizeData,
        ]);
    }
}
