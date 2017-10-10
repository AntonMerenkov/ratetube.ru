<?php

namespace app\models;

use app\components\YoutubeAPI;
use Yii;

/**
 * This is the model class for table "api_keys".
 *
 * @property integer $id
 * @property string $key
 *
 * @property ApiKeyStatistics[] $apiKeyStatistics
 * @property ApiKeyStatistics $lastStatistics
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
            [['key'], 'unique'],
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
     * @return \yii\db\ActiveQuery
     */
    public function getLastStatistics()
    {
        return $this->hasOne(ApiKeyStatistics::className(), ['api_key_id' => 'id'])
            ->orderBy(['date' => SORT_DESC]);
    }

    /**
     * Проверка API-ключа.
     *
     * @param $key
     * @return bool|array
     */
    public static function validateKey($key)
    {
        $res = Yii::$app->curl->querySingle('https://www.googleapis.com/youtube/v3/videos?' . http_build_query(array(
                'part' => 'snippet',
                'id' => 'test',
                'key' => $key
            )));

        $result = json_decode($res, true);

        if (isset($result[ 'error' ]))
            return [
                'status' => 0,
                'error' => $result[ 'error' ][ 'errors' ][ 0 ][ 'message' ]
            ];

        return is_array($result) && isset($result[ 'items' ]) ? [
            'status' => 1
        ] : [
            'status' => 0,
            'error' => 'Ошибка',
        ];
    }
}
