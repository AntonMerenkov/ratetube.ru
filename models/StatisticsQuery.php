<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Statistics]].
 *
 * @see Statistics
 */
class StatisticsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Statistics[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Statistics|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
