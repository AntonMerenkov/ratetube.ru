<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "slave_profiling".
 *
 * @property integer $id
 * @property integer $slave_id
 * @property string $datetime
 * @property string $duration
 * @property string $size
 * @property integer $count
 * @property integer $type
 * @property string $method
 * @property string $parts
 *
 * @property Slaves $slave
 */
class SlaveProfiling extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'slave_profiling';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['slave_id', 'datetime', 'duration', 'size', 'count', 'type', 'method', 'parts'], 'required'],
            [['slave_id', 'count', 'type'], 'integer'],
            [['datetime'], 'safe'],
            [['duration', 'size'], 'number'],
            [['method'], 'string', 'max' => 40],
            [['parts'], 'string', 'max' => 255],
            [['slave_id'], 'exist', 'skipOnError' => true, 'targetClass' => Slaves::className(), 'targetAttribute' => ['slave_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'slave_id' => 'Сервер',
            'datetime' => 'Время',
            'duration' => 'Время выполнения',
            'size' => 'Объем данных',
            'count' => 'Количество элементов',
            'type' => 'Тип запроса',
            'method' => 'Путь',
            'parts' => 'Части',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSlave()
    {
        return $this->hasOne(Slaves::className(), ['id' => 'slave_id']);
    }
}
