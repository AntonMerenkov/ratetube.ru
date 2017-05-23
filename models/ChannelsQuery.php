<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Channels]].
 *
 * @see Channels
 */
class ChannelsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Channels[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Channels|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
