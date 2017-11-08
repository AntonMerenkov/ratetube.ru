<?php

namespace app\controllers;

use app\models\AdStatistics;
use DateInterval;
use DateTime;
use Yii;
use app\models\Ads;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\validators\IpValidator;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AdsController implements the CRUD actions for Ads model.
 */
class AdsController extends Controller
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
                        'actions' => ['file'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
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
     * Lists all Ads models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Ads::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Ads model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $statisticsData = ArrayHelper::map(AdStatistics::find()->where(['ad_id' => $id])->orderBy(['date' => SORT_ASC])->all(), function($item) {
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
     * Creates a new Ads model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Ads();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Ads model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Ads model.
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
     * Finds the Ads model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Ads the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Ads::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Отдача файла клиенту.
     *
     * @param $uuid
     * @param int|null $no_stat Не подсчитывать статистику при обращении (для административного раздела)
     * @return $this
     * @throws NotFoundHttpException
     */
    public function actionFile($uuid, $no_stat = null)
    {
        $ad = Ads::find()->where(['uuid' => $uuid])->one();

        if (is_null($ad))
            throw new NotFoundHttpException('Файл не найден.');

        // подсчет статистики
        if (is_null($no_stat)) {
            $statistics = AdStatistics::find()->where([
                'ad_id' => $ad->id,
                'date' => date('Y-m-d')
            ])->one();

            if (is_null($statistics)) {
                $statistics = new AdStatistics();
                $statistics->ad_id = $ad->id;
                $statistics->date = date('Y-m-d');
                $statistics->views = 0;
            }

            $statistics->views++;
            $statistics->save();
        }

        return Yii::$app->response->sendFile($ad->getPath());
    }
}
