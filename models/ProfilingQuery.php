<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Profiling]].
 *
 * @see Profiling
 */
class ProfilingQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Profiling[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Profiling|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
