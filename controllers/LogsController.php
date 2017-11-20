<?php

namespace app\controllers;

use app\models\SecurityIp;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\validators\IpValidator;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use zhuravljov\yii\logreader\Log;

/**
 * Default controller for the `logreader` module
 */
class LogsController extends \zhuravljov\yii\logreader\controllers\DefaultController
{
    public $layout = '/admin';

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
}