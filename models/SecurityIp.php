<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "security_ip".
 *
 * @property integer $id
 * @property string $ip
 */
class SecurityIp extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'security_ip';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ip'], 'required'],
            [['ip'], 'ip', 'ipv6' => false, 'subnet' => null],
            [['ip'], 'string', 'max' => 32],
            [['ip'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'IP-адрес (маска)',
        ];
    }
}
