<?php

namespace app\controllers;

use app\commands\AgentController;
use app\components\Statistics;
use app\models\Categories;
use app\models\SecurityIp;
use app\models\Videos;
use app\models\VideosSearch;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Worksheet_PageSetup;
use Yii;
use app\models\Channels;
use app\models\ChannelsSearch;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\validators\IpValidator;
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
                            $adminIP = ArrayHelper::map(SecurityIp::find()->all(), 'id', 'ip');

                            if (empty($adminIP))
                                return true;

                            $validator = new IpValidator([
                                'ranges' => $adminIP
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
     * Creates a new Channels models.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @param $category_id
     * @return mixed
     */
    public function actionCreateList($category_id)
    {
        $count = count(Yii::$app->request->post('Channels', []));
        $channels = [ new Channels() ];
        for ($i = 1; $i < $count; $i++) {
            $channels[ $i ] = new Channels();
            $channels[ $i ]->category_id = $category_id;
        }

        Channels::loadMultiple($channels, Yii::$app->request->post());
        array_walk($channels, function($item) use ($category_id) {
            $item->category_id = $category_id;

            if ($item->name == '')
                $item->name = end(explode('/', $item[ 'url' ]));
        });

        // проверка на уникальность среди данных
        $ids = ArrayHelper::map(Channels::find()->all(), 'id', 'channel_link');
        foreach ($channels as $id => $channel)
            if (in_array($channel->channel_link, $ids))
                unset($channels[ $id ]);
            else
                $ids[] = $channel->channel_link;

        $channels = array_values($channels);
        if (empty($channels))
            $channels = [ new Channels() ];

        if (Channels::validateMultiple($channels)) {
            foreach ($channels as $channel)
                $channel->save();

            return $this->redirect(['index', 'id' => $category_id]);
        } else {
            return $this->render('create-list', [
                'channels' => $channels,
                'category_id' => $category_id
            ]);
        }
    }

    /**
     * Поиск каналов по ключевым словам.
     *
     * @param $category_id
     * @return string
     */
    public function actionSearch($category_id)
    {
        $postData = array_values(Yii::$app->request->post('Channels', []));

        // отфильтровываем невключенные элементы
        $postData = array_filter($postData, function($item) {
            return isset($item[ 'checked' ]) && $item[ 'checked' ];
        });

        $count = count($postData);
        $channels = [ new Channels() ];
        for ($i = 1; $i < $count; $i++) {
            $channels[ $i ] = new Channels();
            $channels[ $i ]->category_id = $category_id;
        }

        Channels::loadMultiple($channels, ['Channels' => $postData]);
        array_walk($channels, function($item) use ($category_id) {
            $item->category_id = $category_id;

            if ($item->name == '')
                $item->name = end(explode('/', $item[ 'url' ]));
        });

        // проверка на уникальность среди данных
        $ids = ArrayHelper::map(Channels::find()->all(), 'id', 'channel_link');
        foreach ($channels as $id => $channel)
            if (in_array($channel->channel_link, $ids))
                unset($channels[ $id ]);
            else
                $ids[] = $channel->channel_link;

        $channels = array_values($channels);
        if (empty($channels))
            $channels = [ new Channels() ];

        if (Channels::validateMultiple($channels)) {
            foreach ($channels as $channel)
                $channel->save();

            return $this->redirect(['index', 'id' => $category_id]);
        } else {
            return $this->render('search', [
                'channels' => $channels,
                'category_id' => $category_id
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
        $model = $this->findModel($id);
        $model->delete();

        return $this->redirect(['index', 'id' => $model->category_id]);
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
     * Поиск каналов по запросу.
     *
     * @return string
     */
    public function actionSearchData()
    {
        if ($_POST[ 'query' ] != '')
            return Json::encode(Channels::searchData($_POST[ 'query' ], (int) $_POST[ 'count' ], (int) $_POST[ 'subscribers' ]));
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

        $statisticsData = Statistics::getStatistics(1, [
            //'timeType' => Statistics::QUERY_TIME_DAY,
            //'sortType' => Statistics::SORT_TYPE_VIEWS_DIFF,
            'channel_id' => $id,
            'fullData' => true,
            'findCached' => true,
        ]);

        return $this->render('list-videos', [
            'channelModel' => $channelModel,
            'searchModel' => $searchModel,
            'videosDataProvider' => $videosDataProvider,
            'statisticsData' => $statisticsData,
        ]);
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

    /**
     * Экспорт списка каналов в Excel.
     *
     * @param null $category_id
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function actionExportList($category_id = null)
    {
        $models = Channels::find()->where(is_null($category_id) ? [] : ['category_id' => $category_id])->orderBy(['subscribers_count' => SORT_DESC])->all();

        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getProperties()->setCreator(Yii::$app->name)->setTitle('Список каналов');

        $objPHPExcel->setActiveSheetIndex(0);

        $sheet = $objPHPExcel->getActiveSheet();

        $sheet->getDefaultRowDimension()->setRowHeight(20);

        $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(100);
        $sheet->getPageMargins()->setTop(1 / 2.54);
        $sheet->getPageMargins()->setBottom(1 / 2.54);
        $sheet->getPageMargins()->setLeft(1 / 2.54);
        $sheet->getPageMargins()->setRight(1 / 2.54);

        $sheet->setCellValue('A1', 'Название канала');
        $sheet->setCellValue('B1', 'URL');
        $sheet->setCellValue('C1', 'Подписчиков');

        $sheet->getColumnDimension('A')->setWidth(40);
        $sheet->getColumnDimension('B')->setWidth(70);
        $sheet->getColumnDimension('C')->setWidth(20);

        $index = 2;
        foreach ($models as $model) {
            $sheet->setCellValue('A' . $index, $model->name);
            $sheet->setCellValue('B' . $index, $model->url);
            $sheet->setCellValue('C' . $index, (int) $model->subscribers_count);

            $index++;
        }

        $sheet->getStyle('A1:C1')->applyFromArray([
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                ],
                'outline' => [
                    'style' => PHPExcel_Style_Border::BORDER_MEDIUM
                ],
            ],
            'font' => [
                'bold' => true
            ]
        ]);

        $sheet->getStyle('A2:C' . ($index - 1))->applyFromArray([
            'borders' => [
                'allborders' => [
                    'style' => PHPExcel_Style_Border::BORDER_THIN
                ],
                'outline' => [
                    'style' => PHPExcel_Style_Border::BORDER_MEDIUM
                ],
            ],
        ]);

        $sheet->getStyle('C2:C' . ($index - 1))->applyFromArray([
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="channels.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }
}
