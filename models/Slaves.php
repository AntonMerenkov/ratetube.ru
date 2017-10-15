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
}
