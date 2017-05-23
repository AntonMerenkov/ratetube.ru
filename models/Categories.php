<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "categories".
 *
 * @property integer $id
 * @property string $name
 * @property string $code
 *
 * @property Channels[] $channels
 */
class Categories extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'categories';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'code'], 'required'],
            [['code'], 'match', 'pattern' => '/^[a-z\-\_0-9]+$/i', 'message' => 'Символьный код может содержать буквы латинского алфавита, цифры, знаки тире и подчеркивания.'],
            [['name', 'code'], 'string', 'max' => 255],
            [['code'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Наименование',
            'code' => 'Символьный код',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChannels()
    {
        return $this->hasMany(Channels::className(), ['category_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return CategoriesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new CategoriesQuery(get_called_class());
    }
}
