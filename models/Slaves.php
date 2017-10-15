<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "slaves".
 *
 * @property integer $id
 * @property string $ip
 */
class Slaves extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'slaves';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ip'], 'required'],
            [['ip'], 'unique'],
            [['ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'IP-адрес',
        ];
    }

    /**
     * Проверка подчиненного сервера.
     *
     * @param $ip
     * @return bool|array
     */
    public static function validateServer($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP))
            return [
                'status' => 0,
                'error' => 'IP-адрес задан неверно.'
            ];

        $res = Yii::$app->curl->querySingle('http://' . $ip . '/test?' . http_build_query([
            'key' => \Yii::$app->request->cookieValidationKey
        ]));

        $result = json_decode($res, true);

        if (isset($result[ 'status' ]) && $result[ 'status' ] == 1)
            return [
                'status' => 1,
            ];
        else
            return [
                'status' => 0,
                'error' => 'Данный сервер настроен неверно.' . print_r($result, true)
            ];
    }
}
