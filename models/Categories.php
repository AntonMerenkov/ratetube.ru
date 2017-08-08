<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "categories".
 *
 * @property integer $id
 * @property string $name
 * @property string $code
 * @property string $flush_timeframe
 * @property integer $flush_count
 *
 * @property Channels[] $channels
 */
class Categories extends \yii\db\ActiveRecord
{
    public $timeframeExist;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'categories';
    }

    /**
     * Проверка корректности критерия удаления.
     *
     * @param $attribute
     * @param $params
     */
    public function timeframeCheck($attribute, $params)
    {
        if ($this->$attribute)
            if ($this->flush_timeframe == '' || $this->flush_count <= 0)
                $this->addError($attribute, 'Укажите корректные данные для критерия удаления.');
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
            [['flush_count'], 'integer'],
            [['flush_timeframe'], 'string', 'max' => 20],
            [['timeframeExist'], 'boolean'],
            [['timeframeExist'], 'timeframeCheck'],
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
            'flush_timeframe' => 'Период очистки',
            'flush_count' => 'Минимальное количество просмотров',
            'timeframeExist' => 'Удалять видео по критерию',
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        $this->timeframeExist = ($this->flush_timeframe != '') && ($this->flush_count > 0);
    }

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if (!$this->timeframeExist) {
            $this->flush_timeframe = null;
            $this->flush_count = null;
        }

        return parent::beforeValidate();
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
