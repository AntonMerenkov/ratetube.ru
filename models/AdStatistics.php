<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ad_statistics".
 *
 * @property integer $id
 * @property integer $ad_id
 * @property string $date
 * @property integer $views
 */
class AdStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ad_statistics';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ad_id', 'date'], 'required'],
            [['ad_id', 'views'], 'integer'],
            [['date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ad_id' => 'Реклама',
            'date' => 'Дата',
            'views' => 'Показов',
        ];
    }
}
