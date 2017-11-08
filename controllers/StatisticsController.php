<?php

namespace app\controllers;

use app\components\YoutubeAPI;
use app\models\ApiKeys;
use app\models\Categories;
use app\models\Profiling;
use app\components\Statistics;
use DateInterval;
use DateTime;
use Yii;
use app\models\Channels;
use app\models\ChannelsSearch;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\validators\IpValidator;
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
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'ip' => [
                'class' => AccessControl::className(),
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
     * Lists all Channels models.
     * @return mixed
     */
    public function actionIndex()
    {
        ini_set('memory_limit', '1024M');

        // использование API-ключей
        $keys = ApiKeys::find()->all();
        $keysError = null;
        if (!empty($keys)) {
            $currentDate = new DateTime();
            if ($currentDate < new DateTime(date('d.m.Y') . ' ' . YoutubeAPI::QUOTA_REFRESH_TIME))
                $currentDate->sub(new DateInterval('P1D'));

            $keysFilled = array_map(function($item) use ($currentDate) {
                if (is_null($item->lastStatistics))
                    return false;

                return ($item->lastStatistics->date == $currentDate->format('Y-m-d')) &&
                ($item->lastStatistics->quota >= YoutubeAPI::MAX_QUOTA_VALUE) ? 1 : 0;
            }, $keys);

            if (count($keysFilled) == array_sum($keysFilled))
                $keysError = 'В данный момент квота по всем ключам израсходована, запросы к YouTube API невозможны.<br>
                    Добавьте еще ключей YouTube API.';
        } else {
            $keysError = 'Ни одного ключа не добавлено.';
        }

        // агенты (за 7 дней)
        $profilingData = Profiling::find()->where([
            '>', 'datetime', date('Y-m-d H:i:s', time() - 86400 * 7)
        ])->all();

        $profilingTableData = [];
        foreach ($profilingData as $item) {
            $profilingTableData[ $item->code ][ 'code' ] = $item->code;
            $profilingTableData[ $item->code ][ 'duration' ][] = $item->duration;
            $profilingTableData[ $item->code ][ 'memory' ][] = $item->memory;
        }

        $profilingDataProvider = new ActiveDataProvider([
            'models' => array_values(array_map(function($item) {
                $item[ 'duration' ] = array_filter($item[ 'duration' ], function($item2) {
                    return $item2 > 0;
                });
                $item[ 'memory' ] = array_filter($item[ 'memory' ], function($item2) {
                    return $item2 > 0;
                });

                return [
                    'code' => $item[ 'code' ],
                    'duration_avg' => round(array_sum($item[ 'duration' ]) / count($item[ 'duration' ]), 2),
                    'duration_max' => max($item[ 'duration' ]),
                    'memory_avg' => round(array_sum($item[ 'memory' ]) / count($item[ 'memory' ]), 2),
                    'memory_max' => max($item[ 'memory' ]),
                ];
            }, $profilingTableData)),
            'pagination' => false
        ]);

        $videosData = array_values(array_filter($profilingData, function($item) {
            return $item->code == 'agent-update-videos';
        }));

        if (count($videosData) > 1000)
            $videosData = array_map(function($item) {
                return $item[ 0 ];
            }, array_chunk($videosData, floor(count($videosData) / 1000)));

        $videosDataProvider = new ActiveDataProvider([
            'models' => $videosData,
            'pagination' => false
        ]);

        $statisticsData = array_values(array_filter($profilingData, function($item) {
            return $item->code == 'agent-update-statistics';
        }));

        if (count($statisticsData) > 1000)
            $statisticsData = array_map(function($item) {
                return $item[ 0 ];
            }, array_chunk($statisticsData, floor(count($statisticsData) / 1000)));

        $statisticsDataProvider = new ActiveDataProvider([
            'models' => $statisticsData,
            'pagination' => false
        ]);

        // статистика по запросам
        $statisticsQueryData = Statistics::getStatistics(1, [
            'findCached' => true
        ]);

        // размер таблиц БД
        $tableSizeData = Statistics::getTableSizeData();

        // наполняемость БД
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
            'keysError' => $keysError,
            'videosDataProvider' => $videosDataProvider,
            'statisticsDataProvider' => $statisticsDataProvider,
            'statisticsQueryData' => $statisticsQueryData,
            'statisticsDatesData' => $statisticsDatesData,
            'tableSizeData' => $tableSizeData,
            'profilingDataProvider' => $profilingDataProvider,
        ]);
    }
}
