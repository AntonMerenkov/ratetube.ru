<?php

namespace app\controllers;

use app\components\YoutubeAPI;
use app\models\ApiKeyStatistics;
use DateInterval;
use DateTime;
use Yii;
use app\models\ApiKeys;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ApiKeysController implements the CRUD actions for ApiKeys model.
 */
class ApiKeysController extends Controller
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
     * Lists all ApiKeys models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => ApiKeys::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ApiKeys model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $statisticsData = ArrayHelper::map(ApiKeyStatistics::find()->where(['api_key_id' => $id])->orderBy(['date' => SORT_ASC])->all(), function($item) {
            return date('d.m.Y', strtotime($item->date));
        }, 'quota');

        $statistics = [];
        if (!empty($statisticsData)) {
            $minDate = new DateTime(key($statisticsData));
            $currentDate = new DateTime();
            if ($currentDate < new DateTime(date('d.m.Y') . ' ' . YoutubeAPI::QUOTA_REFRESH_TIME))
                $currentDate->sub(new DateInterval('P1D'));

            while ($minDate <= $currentDate) {
                if (!array_key_exists($minDate->format('d.m.Y'), $statisticsData))
                    $statisticsData[ $minDate->format('d.m.Y') ] = null;

                $minDate->add(new DateInterval('P1D'));
            }

            uksort($statisticsData, function($a, $b) {
                return strtotime($b) - strtotime($a);
            });

            $statistics = [];
            foreach ($statisticsData as $date => $quota)
                $statistics[] = [
                    'date' => $date,
                    'quota' => $quota,
                ];
        }

        $statisticsProvider = new ArrayDataProvider([
            'allModels' => $statistics,
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);

        return $this->render('view', [
            'model' => $this->findModel($id),
            'statisticsProvider' => $statisticsProvider,
        ]);
    }

    /**
     * Creates a new ApiKeys model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ApiKeys();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing ApiKeys model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing ApiKeys model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the ApiKeys model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ApiKeys the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ApiKeys::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Валидация API-ключа.
     *
     * @return string
     */
    public function actionQueryData()
    {
        return Json::encode(isset($_POST[ 'key' ]) && $_POST[ 'key' ] != '' ? ApiKeys::validateKey($_POST[ 'key' ]) : [
            'status' => 0,
            'error' => 'Укажите ключ.'
        ]);
    }
}
