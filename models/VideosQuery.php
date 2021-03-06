<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Videos]].
 *
 * @see Videos
 */
class VideosQuery extends \yii\db\ActiveQuery
{
    public function active()
    {
        return $this->andWhere('[[active]]=1');
    }

    /**
     * @inheritdoc
     * @return Videos[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Videos|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
