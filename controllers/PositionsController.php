<?php

namespace app\controllers;

use app\models\PositionStatistics;
use app\models\Videos;
use DateInterval;
use DateTime;
use Yii;
use app\models\Positions;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\validators\IpValidator;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PositionsController implements the CRUD actions for Positions model.
 */
class PositionsController extends Controller
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
                            if ($action->id == 'file')
                                return true;

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
     * Lists all Positions models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Positions::find()->orderBy(['position' => SORT_ASC]),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Positions model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $statisticsData = ArrayHelper::map(PositionStatistics::find()->where(['position_id' => $id])->orderBy(['date' => SORT_ASC])->all(), function($item) {
            return date('d.m.Y', strtotime($item->date));
        }, 'views');

        $statistics = [];
        if (!empty($statisticsData)) {
            $minDate = new DateTime(key($statisticsData));
            $currentDate = new DateTime();

            while ($minDate <= $currentDate) {
                if (!array_key_exists($minDate->format('d.m.Y'), $statisticsData))
                    $statisticsData[ $minDate->format('d.m.Y') ] = null;

                $minDate->add(new DateInterval('P1D'));
            }

            uksort($statisticsData, function($a, $b) {
                return strtotime($b) - strtotime($a);
            });

            $statistics = [];
            foreach ($statisticsData as $date => $views)
                $statistics[] = [
                    'date' => $date,
                    'views' => $views,
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
     * Creates a new Positions model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Positions();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Positions model.
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
     * Deletes an existing Positions model.
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
     * Finds the Positions model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Positions the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Positions::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Получение данных о видео по URL.
     *
     * @return string
     */
    public function actionQueryData()
    {
        if ($_POST[ 'url' ] != '')
            return Json::encode(Videos::queryData($_POST[ 'url' ]));
        else
            return Json::encode([
                'status' => 0
            ]);
    }
}
