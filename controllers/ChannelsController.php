<?php

namespace app\controllers;

use app\commands\AgentController;
use app\models\Categories;
use app\models\Videos;
use app\models\VideosSearch;
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
 * ChannelsController implements the CRUD actions for Channels model.
 */
class ChannelsController extends Controller
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
    public function actionIndex($id = null)
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Categories::find(),
            'pagination' => false,
        ]);

        $searchModel = new ChannelsSearch();

        if (!is_null($id)) {
            $category = Categories::findOne($id);
            $searchModel->category_id = $id;
        } else {
            $category = null;
            $searchModel->category_id = -1;
        }

        if ($_GET[ 'ChannelsSearch' ] && ($searchModel->category_id == -1))
            $searchModel->category_id = null;

        $channelDataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'category' => $category,
            'channelDataProvider' => $channelDataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single Channels model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Channels model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param $category_id
     * @return mixed
     */
    public function actionCreate($category_id)
    {
        $model = new Channels();

        $model->category_id = $category_id;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id' => $model->category_id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Channels model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id' => $model->category_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Channels model.
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
     * Получение данных о канале по URL.
     *
     * @return string
     */
    public function actionQueryData()
    {
        if ($_POST[ 'url' ] != '')
            return Json::encode(Channels::queryData($_POST[ 'url' ]));
        else
            return Json::encode([]);
    }

    /**
     * Finds the Channels model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Channels the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Channels::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finds the Categories model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Categories the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findCategoryModel($id)
    {
        if (($model = Categories::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finds the Videos model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Videos the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findVideosModel($id)
    {
        if (($model = Videos::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Creates a new Categories model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateCategory()
    {
        $model = new Categories();
        $model->loadDefaultValues();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id' => $model->id]);
        } else {
            return $this->render('/categories/create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Categories model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdateCategory($id)
    {
        $model = $this->findCategoryModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id' => $model->id]);
        } else {
            return $this->render('/categories/update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Categories model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDeleteCategory($id)
    {
        $this->findCategoryModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Восстановление неактуальных видео.
     *
     * @param $id
     */
    public function actionRestore($id)
    {
        $model = $this->findModel($id);

        Videos::updateAll(['active' => 1], ['channel_id' => $id]);

        $this->redirect(['index', 'id' => $model->category_id]);
    }

    /**
     * Список видео канала.
     *
     * @param $id
     * @return string
     */
    public function actionListVideos($id) {
        $channelModel = $this->findModel($id);

        $searchModel = new VideosSearch();
        $searchModel->channel_id = $id;

        $videosDataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('list-videos', [
            'channelModel' => $channelModel,
            'searchModel' => $searchModel,
            'videosDataProvider' => $videosDataProvider,
        ]);
    }

    /**
     * Загрузка списка видео канала вручную.
     *
     * @param $id
     */
    public function actionReload($id) {
        set_time_limit(300);

        $channelModel = $this->findModel($id);

        $consoleController = new AgentController('agent', null);
        $consoleController->actionUpdateVideos($id);

        $this->redirect(['list-videos', 'id' => $id]);
    }

    /**
     * Удаление видео.
     *
     * @param $id
     */
    public function actionDeleteVideo($id) {
        $model = $this->findVideosModel($id);

        $model->delete();

        $this->redirect(['list-videos', 'id' => $model->channel_id]);
    }
}
