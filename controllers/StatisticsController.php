<?php

namespace app\controllers;

use app\models\Categories;
use app\models\Profiling;
use app\models\Statistics;
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
        $videosDataProvider = new ActiveDataProvider([
            'query' => Profiling::find()->where([
                'code' => 'agent-update-videos'
            ]),
            'pagination' => false
        ]);

        $statisticsDataProvider = new ActiveDataProvider([
            'query' => Profiling::find()->where([
                'code' => 'agent-update-statistics'
            ]),
            'pagination' => false
        ]);

        $statisticsQueryData = Statistics::getStatistics();

        return $this->render('index', [
            'videosDataProvider' => $videosDataProvider,
            'statisticsDataProvider' => $statisticsDataProvider,
            'statisticsQueryData' => $statisticsQueryData,
        ]);
    }
}
