<?php

namespace app\components;
use app\models\ApiKeys;
use app\models\ApiKeyStatistics;
use DateInterval;
use DateTime;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Доступ к YouTube Data API.
 *
 * Class YoutubeAPI
 * @package app\components
 */
class YoutubeAPI
{
    /**
     * Типы запросов.
     */
    const QUERY_DEFAULT = 0; // одиночный запрос
    const QUERY_MULTIPLE = 1; // множественный запрос с разбиением id
    const QUERY_PAGES = 2; // постраничный запрос (по 1 запросу на каждый id, загружать последовательно все страницы)

    const MAX_RESULTS = 50; // максимальное кол-во результатов на 1 страницу

    const MAX_QUOTA_VALUE = 1000000; // максимальная квота
    const QUOTA_REFRESH_TIME = '10:10:00';

    /**
     * @var array Ключи для доступа к API
     * [
     *  'id',
     *  'key',
     *  'quota',
     *  'quota_diff' (для сохранения),
     *  'enabled'
     * ]
     */
    private static $keys = [];

    /**
     * @var array Стоимость запросов.
     */
    private static $quotaCost = [
        'search' => [
            'snippet' => 100,
        ],
        'videos' => [
            'snippet' => 3,
            'contentDetails' => 3,
            'status' => 3,
            'statistics' => 3,
            'player' => 1,
            'topicDetails' => 3,
            'recordingDetails' => 3,
            'fileDetails' => 2,
            'processingDetails' => 2,
            'suggestions' => 2,
        ],
        'channels' => [
            'snippet' => 3,
            'brandingSettings' => 3,
            'contentDetails' => 3,
            'invideoPromotion' => 3,
            'statistics' => 3,
            'status' => 3,
            'topicDetails' => 3,
        ],
    ];

    /**
     * Запрос к YouTube Data API.
     *
     * @param $method
     * @param array $params
     * @param array $parts
     * @param int $type
     * @return bool|mixed
     */
    public static function query($method, $params = [], $parts = ['snippet'], $type = self::QUERY_DEFAULT)
    {
        $time = microtime(true);
        $quotaValue = self::getQuotaValue();

        if ($type == self::QUERY_DEFAULT) {
            $key = self::getKey($method, $parts);

            if ($key === false) {
                Yii::warning('Запрос "' . $method . '" не может быть выполнен: нет активных API-ключей.', 'agent');
                return false;
            }

            // одиночный запрос
            do {
                $res = Yii::$app->curl->querySingle('https://www.googleapis.com/youtube/v3/' . $method . '?' . http_build_query([
                            'part' => implode(',', $parts),
                            'key' => $key
                        ] + $params));

                $result = json_decode($res, true);

                if (!is_array($result))
                    continue;

                if (!isset($result[ 'error' ])) {
                    Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                        Yii::$app->formatter->asDecimal(self::getQuotaValue() - $quotaValue) . ', время - ' .
                        Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'api-keys');

                    return $result[ 'items' ];
                }

                // если есть ошибка - запрещаем API-ключ и выполняем еще один запрос, пока не вернется результат или пока не кончатся ключи
                Yii::error('Ошибка YouTube: ' . $result[ 'error' ][ 'errors' ][ 0 ][ 'message' ], 'api-keys');
                self::disableKey($key, $result[ 'error' ][ 'errors' ][ 0 ][ 'reason' ] == 'quotaExceeded');

                $key = self::getKey($method, $parts);
            } while ($key !== false);
        } else if ($type == self::QUERY_MULTIPLE) {
            // множественный запрос
            $ids = $params[ 'id' ];
            $idChunks = array_chunk($ids, self::MAX_RESULTS);

            $resultArray = [];

            do {
                $urlArray = [];
                $keysArray = [];

                // формируем несколько запросов
                foreach ($idChunks as $id => $idChunk) {
                    if (isset($resultArray[ $id ]))
                        continue;

                    $key = self::getKey($method, $parts);
                    $keysArray[ $id ] = $key;

                    if ($key === false) {
                        Yii::warning('Запрос "' . $method . '" не может быть выполнен: нет активных API-ключей.', 'agent');
                        return false;
                    }

                    $urlArray[ $id ] = 'https://www.googleapis.com/youtube/v3/' . $method . '?' . http_build_query([
                                'id' => implode(',', $idChunk),
                                'part' => implode(',', $parts),
                                'key' => $key,
                                'maxResults' => self::MAX_RESULTS
                            ] + $params);
                }

                $responseArray = array_map(function($item) {
                    return json_decode($item, true);
                }, Yii::$app->curl->queryMultiple($urlArray));

                // если что-то не загрузилось - запрещаем ключи и формируем новые запросы
                foreach ($urlArray as $id => $url) {
                    if (!isset($responseArray[ $id ]))
                        continue;

                    if (!isset($responseArray[ $id ][ 'error' ])) {
                        $resultArray[ $id ] = $responseArray[ $id ][ 'items' ];
                        unset($urlArray[ $id ]);
                    } else {
                        Yii::error('Ошибка YouTube: ' . $responseArray[ $id ][ 'error' ][ 'errors' ][ 0 ][ 'message' ], 'api-keys');
                        self::disableKey($keysArray[ $id ], $responseArray[ $id ][ 'error' ][ 'errors' ][ 0 ][ 'reason' ] == 'quotaExceeded');
                    }
                }
            } while (!empty($urlArray));

            $result = [];
            foreach ($resultArray as $resultItem)
                foreach ($resultItem as $resultValue)
                    $result[] = $resultValue;

            Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                Yii::$app->formatter->asDecimal(self::getQuotaValue() - $quotaValue) . ', время - ' .
                Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'api-keys');

            return $result;
        } else if ($type == self::QUERY_PAGES) {
            // постраничный запрос
            $ids = $params[ 'channelId' ];

            $resultArray = [];
            $pageTokens = [];

            do {
                $urlArray = [];
                $keysArray = [];

                // формируем несколько запросов
                foreach ($ids as $id => $value) {
                    if (isset($resultArray[ $id ]) && $pageTokens[ $id ] == '')
                        continue;

                    $key = self::getKey($method, $parts);
                    $keysArray[ $id ] = $key;

                    if ($key === false) {
                        Yii::warning('Запрос "' . $method . '" не может быть выполнен: нет активных API-ключей.', 'agent');
                        return false;
                    }

                    $urlArray[ $id ] = 'https://www.googleapis.com/youtube/v3/' . $method . '?' . http_build_query([
                                'channelId' => $value,
                                'part' => implode(',', $parts),
                                'key' => $key,
                                'maxResults' => self::MAX_RESULTS,
                            ] + $params + (isset($pageTokens[ $id ]) && $pageTokens[ $id ] != '' ? ['pageToken' => $pageTokens[ $id ]] : []));
                }

                $responseArray = array_map(function($item) {
                    return json_decode($item, true);
                }, Yii::$app->curl->queryMultiple($urlArray));

                // если что-то не загрузилось - запрещаем ключи и формируем новые запросы
                foreach ($urlArray as $id => $url) {
                    if (!isset($responseArray[ $id ]))
                        continue;

                    if (!isset($responseArray[ $id ][ 'error' ])) {
                        foreach ($responseArray[ $id ][ 'items' ] as $item)
                            $resultArray[ $id ][] = $item;

                        // формируем запросы на загрузку новых страниц
                        $pageTokens[ $id ] = $responseArray[ $id ][ 'nextPageToken' ];

                        if ($pageTokens[ $id ] == '')
                            unset($urlArray[ $id ]);
                    } else {
                        Yii::error('Ошибка YouTube: ' . $responseArray[ $id ][ 'error' ][ 'errors' ][ 0 ][ 'message' ], 'api-keys');
                        self::disableKey($keysArray[ $id ], $responseArray[ $id ][ 'error' ][ 'errors' ][ 0 ][ 'reason' ] == 'quotaExceeded');
                    }
                }
            } while (!empty($urlArray));

            $result = [];
            foreach ($resultArray as $resultItem)
                foreach ($resultItem as $resultValue)
                    $result[] = $resultValue;

            Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                Yii::$app->formatter->asDecimal(self::getQuotaValue() - $quotaValue) . ', время - ' .
                Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'api-keys');

            return $result;
        }

        return false;
    }

    /**
     * Получение API-ключа.
     * Добавление квоты для ключа.
     *
     * @param $method
     * @param $parts
     * @return string|bool
     */
    private static function getKey($method, $parts)
    {
        self::loadKeys();

        if (empty(self::$keys)) {
            Yii::warning('Отсутствуют API-ключи.', 'api-keys');
            return false;
        }

        // отфильтровываем ключи, у которых уже кончилась квота
        $keys = array_filter(self::$keys, function($item) {
            return $item[ 'enabled' ] && $item[ 'quota' ] < self::MAX_QUOTA_VALUE;
        });

        if (empty($keys)) {
            Yii::warning('Квота для всех ключей исчерпана.', 'api-keys');
            return false;
        }

        // выбираем ключ с минимальной квотой
        usort($keys, function($a, $b) {
            return $a[ 'quota' ] - $b[ 'quota' ];
        });

        $activeKey = $keys[ 0 ];

        // добавляем квоту
        $quotaCost = 1;
        foreach ($parts as $part)
            if (isset(self::$quotaCost[ $method ][ $part ]))
                $quotaCost += self::$quotaCost[ $method ][ $part ] - 1;

        foreach (self::$keys as $id => $key)
            if ($key[ 'id' ] == $activeKey[ 'id' ]) {
                self::$keys[ $id ][ 'quota' ] += $quotaCost;
                self::$keys[ $id ][ 'quota_diff' ] += $quotaCost;
                break;
            }

        return $activeKey[ 'key' ];
    }

    /**
     * Загрузка ключей API из БД.
     */
    private static function loadKeys()
    {
        if (!empty(self::$keys))
            return;

        $keys = ApiKeys::find()->all();

        foreach ($keys as $key) {
            $quota = 0;
            if (!is_null($key->lastStatistics)) {
                $statDate = new DateTime($key->lastStatistics->date);
                $currentDate = new DateTime();
                if ($currentDate < new DateTime(date('d.m.Y') . ' ' . YoutubeAPI::QUOTA_REFRESH_TIME))
                    $currentDate->sub(new DateInterval('P1D'));

                if ($statDate->format('d.m.Y') == $currentDate->format('d.m.Y'))
                    $quota = $key->lastStatistics->quota;
            }

            if ($quota >= self::MAX_QUOTA_VALUE)
                continue;

            self::$keys[] = [
                'id' => $key->id,
                'key' => $key->key,
                'quota' => $quota,
                'quota_diff' => 0,
                'enabled' => true
            ];
        }
    }

    /**
     * Запрет пользования ключом по причине израсходования квоты.
     *
     * @param $key
     * @param bool $permanent
     */
    private static function disableKey($key, $permanent = false)
    {
        foreach (self::$keys as $id => $value) {
            if ($value[ 'key' ] == $key) {
                self::$keys[ $id ][ 'enabled' ] = false;

                if ($permanent)
                    self::$keys[ $id ][ 'permanent' ] = true;

                return;
            }
        }
    }

    /**
     * Получение значения квоты (для сравнения).
     *
     * @return int
     */
    private function getQuotaValue()
    {
        self::loadKeys();

        return array_sum(array_map(function($item) {
            return $item[ 'quota' ];
        }, self::$keys));
    }

    /**
     * Сохранение данных об использовании квоты в БД.
     * Выполняется при завершении скрипта.
     */
    public static function saveData()
    {
        if (empty(self::$keys))
            return;

        $keyModels = ArrayHelper::map(ApiKeys::find()->all(), 'id', function($item) {
            return $item;
        });

        foreach (self::$keys as $id => $key) {
            if ($key[ 'quota_diff' ] > 0) {
                $model = $keyModels[ $key[ 'id' ] ]->lastStatistics;

                $currentDate = new DateTime();
                if ($currentDate < new DateTime(date('d.m.Y') . ' ' . YoutubeAPI::QUOTA_REFRESH_TIME))
                    $currentDate->sub(new DateInterval('P1D'));

                if (!is_null($model)) {
                    $statDate = new DateTime($model->date);

                    if ($statDate->format('d.m.Y') != $currentDate->format('d.m.Y'))
                        $model = null;
                }

                if (is_null($model)) {
                    $model = new ApiKeyStatistics();
                    $model->api_key_id = $key[ 'id' ];
                    $model->quota = 0;
                    $model->date = $currentDate->format('Y-m-d');
                    $model->save();
                }

                // TODO: после отладки можно включить
                /*if ($key[ 'permanent' ])
                    ApiKeyStatistics::updateAll(['quota' => self::MAX_QUOTA_VALUE], ['id' => $model->id]);
                else
                    ApiKeyStatistics::updateAllCounters(['quota' => $key[ 'quota_diff' ]], ['id' => $model->id]);*/

                ApiKeyStatistics::updateAllCounters(['quota' => $key[ 'quota_diff' ]], ['id' => $model->id]);

                self::$keys[ $id ][ 'quota_diff' ] = 0;
            }
        }
    }
}