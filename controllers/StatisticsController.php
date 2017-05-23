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

        $sql = "select v.name, v.video_link, s.views
from statistics s
left join videos v on v.id = s.video_id
where datetime < '" . date('Y-m-d H:i:s', round(time() / 10 - 5) * 10) . "'
order by views DESC
limit 0,50";

        $sqlTime = microtime(true);
        Yii::$app->db->createCommand($sql)->queryAll();
        $sqlTime = microtime(true) - $sqlTime;

        return $this->render('index', [
            'videosDataProvider' => $videosDataProvider,
            'statisticsDataProvider' => $statisticsDataProvider,
            'sql' => $sql,
            'sqlTime' => $sqlTime,
        ]);
    }
}
