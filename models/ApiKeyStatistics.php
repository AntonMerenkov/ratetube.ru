<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "api_key_statistics".
 *
 * @property integer $id
 * @property integer $api_key_id
 * @property string $date
 * @property integer $quota
 *
 * @property ApiKeys $apiKey
 */
class ApiKeyStatistics extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_key_statistics';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['api_key_id', 'date', 'quota'], 'required'],
            [['api_key_id', 'quota'], 'integer'],
            [['date'], 'safe'],
            [['api_key_id'], 'exist', 'skipOnError' => true, 'targetClass' => ApiKeys::className(), 'targetAttribute' => ['api_key_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'api_key_id' => 'Ключ',
            'date' => 'Дата',
            'quota' => 'Квота',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getApiKey()
    {
        return $this->hasOne(ApiKeys::className(), ['id' => 'api_key_id']);
    }
}
