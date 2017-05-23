<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "profiling".
 *
 * @property integer $id
 * @property string $datetime
 * @property string $code
 * @property string $duration
 */
class Profiling extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'profiling';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['datetime', 'code', 'duration'], 'required'],
            [['datetime'], 'safe'],
            [['duration'], 'number'],
            [['code'], 'string', 'max' => 32],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'datetime' => 'Время',
            'code' => 'Код',
            'duration' => 'Время выполнения',
        ];
    }

    /**
     * @inheritdoc
     * @return ProfilingQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ProfilingQuery(get_called_class());
    }

    /**
     * Форматирование даты.
     *
     * @return bool
     */
    public function beforeValidate()
    {
        $this->datetime = date('Y-m-d H:i:s', strtotime($this->datetime));

        return parent::beforeValidate();
    }
}
