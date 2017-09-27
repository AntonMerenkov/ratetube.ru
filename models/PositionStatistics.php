<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "position_statistics".
 *
 * @property integer $id
 * @property integer $position_id
 * @property string $date
 * @property integer $views
 *
 * @property Positions $position
 */
class PositionStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'position_statistics';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['position_id', 'date'], 'required'],
            [['position_id', 'views'], 'integer'],
            [['date'], 'safe'],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Positions::className(), 'targetAttribute' => ['position_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position_id' => 'Позиция',
            'date' => 'Дата',
            'views' => 'Просмотров',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPosition()
    {
        return $this->hasOne(Positions::className(), ['id' => 'position_id']);
    }
}
