<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "api_keys".
 *
 * @property integer $id
 * @property string $key
 *
 * @property ApiKeyStatistics[] $apiKeyStatistics
 */
class ApiKeys extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'api_keys';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['key'], 'required'],
            [['key'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => 'Ключ',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getApiKeyStatistics()
    {
        return $this->hasMany(ApiKeyStatistics::className(), ['api_key_id' => 'id']);
    }

    /**
     * Проверка API-ключа.
     *
     * @param $key
     * @return bool
     */
    public static function validateKey($key)
    {
        $res = Yii::$app->curl->querySingle('https://www.googleapis.com/youtube/v3/videos?' . http_build_query(array(
                'part' => 'snippet',
                'id' => 'test',
                'key' => $key
            )));

        $result = json_decode($res, true);

        return is_array($result) && isset($result[ 'items' ]);
    }
}
