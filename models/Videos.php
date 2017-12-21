<?php

namespace app\models;

use app\components\HighloadAPI;
use app\components\Statistics;
use app\components\YoutubeAPI;
use Yii;
use yii\helpers\ArrayHelper;
use yii\sphinx\Query;

/**
 * This is the model class for table "videos".
 *
 * @property integer $id
 * @property string $name
 * @property string $video_link
 * @property string $image_url
 * @property integer $channel_id
 * @property integer $active
 *
 * @property Channels $channel
 */
class Videos extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'videos';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['channel_id'], 'integer'],
            [['name', 'image_url'], 'string', 'max' => 255],
            [['video_link'], 'string', 'max' => 32],
            [['channel_id'], 'exist', 'skipOnError' => true, 'targetClass' => Channels::className(), 'targetAttribute' => ['channel_id' => 'id']],
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
            'video_link' => 'ID видео',
            'image_url' => 'Предпросмотр',
            'channel_id' => 'ID канала',
            'active' => 'Активен',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChannel()
    {
        return $this->hasOne(Channels::className(), ['id' => 'channel_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStatistics()
    {
        $timeType = Yii::$app->session->get(Statistics::TIME_SESSION_KEY, Statistics::QUERY_TIME_MINUTE);
        $tableModel = '\\app\\models\\' . Statistics::$tableModels[ $timeType ];

        return $this->hasMany($tableModel::className(), ['video_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return VideosQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new VideosQuery(get_called_class());
    }

    /**
     * Поиск по тэгам.
     * Возвращает ID найденных видео.
     *
     * @param $query
     * @return array
     */
    public static function searchByQuery($query)
    {
        // поиск через Sphinx
        $sphinxQuery = new Query();
        $data = $sphinxQuery->from('tags')
            ->match($query)
            ->limit(1000)
            ->all();

        // сортировка с использованием весовых коэффициентов
        usort($data, function($a, $b) {
            return Tags::$weights[ $b[ 'type' ] ] - Tags::$weights[ $a[ 'type' ] ];
        });

        $data = array_map(function($item) {
            return $item[ 'video_id' ];
        }, $data);

        $data = array_values(array_unique($data));

        return $data;
    }

    /**
     * Получение данных о видео по ссылке.
     *
     * @param $url
     * @return array|mixed
     */
    public static function queryData($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL))
            return ['error' => 'Данный URL не является верным.'];

        if (!preg_match('/watch\?v\=(.+)$/ui', $url, $matches))
            return ['error' => 'Данный URL не является ссылкой на видео.'];

        $videoId = $matches[ 1 ];

        $result = HighloadAPI::query('videos', ['id' => $videoId], ['snippet', 'statistics']);

        if (!empty($result))
            return ['error' => 'Видео не найдено. Возможно, ссылка на видео является неверной.'];

        return [
            'id' => $result[ 0 ][ 'id' ],
            'name' => $result[ 0 ][ 'snippet' ][ 'title' ],
            'image' => $result[ 0 ][ 'snippet' ][ 'thumbnails' ][ 'medium' ][ 'url' ],
        ];
    }

    /**
     * Проверка ID видео.
     *
     * @param $id
     * @return array|mixed
     */
    public static function checkData($id)
    {
        $id = preg_replace('/^#/', '', $id);

        $result = YoutubeAPI::query('videos', ['id' => $id], ['snippet', 'statistics']);

        return [
            'status' => !empty($result) ? 1 : 0,
            'id' => $id
        ];
    }
}
