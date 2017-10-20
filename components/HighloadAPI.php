<?php

namespace app\components;
use app\models\SlaveProfiling;
use app\models\Slaves;
use Exception;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Выполнение параллельного запроса на нескольких серверах.
 *
 * Class HighloadAPI
 * @package app\components
 */
class HighloadAPI
{
    /**
     * @var array Данные профилировщика Highload-запросов.
     */
    private static $profilingData = [];

    /**
     * Получение ключа валидации (для консоли).
     *
     * @return mixed
     */
    private static function getValidationKey()
    {
        try {
            $config = require(Yii::getAlias('@app') . '/config/web.php');
        } catch (Exception $e) {

        }

        return $config[ 'components' ][ 'request' ][ 'cookieValidationKey' ];
    }

    /**
     * Запрос к YouTube Data API.
     *
     * @param $method
     * @param array $params
     * @param array $parts
     * @param int $type
     * @return bool|mixed
     */
    public static function query($method, $params = [], $parts = ['snippet'], $type = YoutubeAPI::QUERY_DEFAULT)
    {
        $time = microtime(true);

        // загружаем ключи
        $quotaValue = YoutubeAPI::getQuotaValue();

        // загружаем список серверов
        $slaveList = ArrayHelper::map(Slaves::find()->all(), 'id', 'ip');

        // если серверов нет - выполняем обычный запрос
        if (empty($slaveList))
            return YoutubeAPI::query($method, $params, $parts, $type);

        $validationKey = self::getValidationKey();

        if ($type == YoutubeAPI::QUERY_DEFAULT) {
            $result = null;

            while (is_null($result)) {
                if (empty($slaveList)) {
                    $result = YoutubeAPI::query($method, $params, $parts, $type);
                } else {
                    // выбираем случайный сервер и делаем запрос через него, если не работает - следующий
                    $serverId = array_rand($slaveList, 1);

                    $response = Yii::$app->curl->querySingle('http://' . $slaveList[ $serverId ] . '/', [
                        'method' => $method,
                        'params' => $params,
                        'parts' => $parts,
                        'type' => $type,
                        'key' => $validationKey,
                        'apiKeys' => YoutubeAPI::$keys
                    ]);

                    try {
                        $value = unserialize($response);
                        $value[ 'result' ] = unserialize(gzuncompress($value[ 'result' ]));
                        $value[ 'length'  ] = strlen($response);
                        $result = $value[ 'result' ];

                        self::$profilingData[] = [
                            'slave_id' => $serverId,
                            'datetime' => date('Y-m-d H:i:s', $time),
                            'duration' => $value[ 'time' ],
                            'size' => round($value[ 'length' ] / 1024 / 1024, 2),
                            'count' => 1,
                            'type' => $type,
                            'method' => $method,
                            'parts' => implode(',', $parts),
                        ];

                        //echo "Время обработки сервером " . $value[ 'ip' ] . ": " . $value[ 'time' ] . " сек. (объем данных - " . (round($value[ 'length' ] / 1024 / 1024, 2)) . " МБ)\n";

                        foreach ($value[ 'keys' ] as $keyId => $keyData) {
                            if (!$keyData[ 'enabled' ])
                                YoutubeAPI::$keys[ $keyId ][ 'enabled' ] = false;

                            if ($keyData[ 'quota_diff' ] > 0) {
                                YoutubeAPI::$keys[ $keyId ][ 'quota' ] += $keyData[ 'quota_diff' ];
                                YoutubeAPI::$keys[ $keyId ][ 'quota_diff' ] += $keyData[ 'quota_diff' ];
                            }
                        }
                    } catch (Exception $e) {
                        unset($slaveList[ $serverId ]);
                    }
                }
            }

            Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                Yii::$app->formatter->asDecimal(YoutubeAPI::getQuotaValue() - $quotaValue) . ', ответов - ' .
                count($result) . ', время - ' .
                Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'highload');

            return $result;
        } else if ($type == YoutubeAPI::QUERY_MULTIPLE) {
            // разделяем запросы на несколько серверов
            $idChunks = array_chunk(array_chunk($params[ 'id' ], YoutubeAPI::MAX_RESULTS), count($slaveList));

            $serverChunks = [];
            foreach ($idChunks as $idChunk)
                foreach ($idChunk as $id => $data)
                    foreach ($data as $value)
                        $serverChunks[ $id ][] = $value;

            $postData = [];
            foreach ($serverChunks as $id => $data)
                $postData[ $id ] = [
                    'method' => $method,
                    'params' => ['id' => $data] + $params,
                    'parts' => $parts,
                    'type' => $type,
                    'key' => $validationKey,
                    'apiKeys' => YoutubeAPI::$keys
                ];

            // выполняем в цикле множественный запрос
            $response = [];
            while (count($response) < count($postData)) {
                $urlArray = [];
                $postArray = [];
                $slaveIds = [];

                if (empty($slaveList)) {
                    // если сервера не работают - выполняем запросы самостоятельно
                    foreach ($postData as $id => $data) {
                        if (isset($response[ $id ]))
                            continue;

                        $response[ $id ] = [
                            'result' => gzcompress(serialize(YoutubeAPI::query($data[ 'method' ], $data[ 'params' ], $data[ 'parts' ], $data[ 'type' ]))),
                            'keys' => []
                        ];
                    }

                    break;
                }

                reset($slaveList);
                foreach ($postData as $id => $data) {
                    if (isset($response[ $id ]))
                        continue;

                    $slaveIds[ $id ] = key($slaveList);
                    $urlArray[ $id ] = 'http://' . current($slaveList) . '/';
                    $postArray[ $id ] = $data;

                    if (next($slaveList) === false)
                        break;
                }

                $responsePart = array_map(function($item) {
                    try {
                        $value = unserialize($item);
                        $value[ 'result' ] = unserialize(gzuncompress($value[ 'result' ]));
                        $value[ 'length'  ] = strlen($item);

                        return $value;
                    } catch (Exception $e) {
                        return false;
                    }
                }, \Yii::$app->curl->queryMultiple($urlArray, $postArray));

                foreach ($responsePart as $id => $value)
                    if (isset($value[ 'result' ])) {
                        $response[ $id ] = $value[ 'result' ];

                        self::$profilingData[] = [
                            'slave_id' => $slaveIds[ $id ],
                            'datetime' => date('Y-m-d H:i:s', $time),
                            'duration' => $value[ 'time' ],
                            'size' => round($value[ 'length' ] / 1024 / 1024, 2),
                            'count' => count($value[ 'result' ]),
                            'type' => $type,
                            'method' => $method,
                            'parts' => implode(',', $parts),
                        ];

                        //echo "Время обработки сервером " . $value[ 'ip' ] . ": " . $value[ 'time' ] . " сек. (" . count($value[ 'result' ]) ." значений, объем данных - " . (round($value[ 'length' ] / 1024 / 1024, 2)) . " МБ)\n";

                        foreach ($value[ 'keys' ] as $keyId => $keyData) {
                            if (!$keyData[ 'enabled' ])
                                YoutubeAPI::$keys[ $keyId ][ 'enabled' ] = false;

                            if ($keyData[ 'quota_diff' ] > 0) {
                                YoutubeAPI::$keys[ $keyId ][ 'quota' ] += $keyData[ 'quota_diff' ];
                                YoutubeAPI::$keys[ $keyId ][ 'quota_diff' ] += $keyData[ 'quota_diff' ];
                            }
                        }
                    } else {
                        unset($slaveList[ $slaveIds[ $id ] ]);
                    }
            }

            /*$result = array_reduce($response, function($carry, $item) {
                foreach ($item as $value)
                    $carry[] = $value;

                return $carry;
            }, []);*/

            Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                Yii::$app->formatter->asDecimal(YoutubeAPI::getQuotaValue() - $quotaValue) . ', зашифрованных ответов - ' .
                count($responsePart) . ', время - ' .
                Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'highload');

            return $response;
        } else if ($type == YoutubeAPI::QUERY_PAGES) {
            // разделяем запросы на несколько серверов по channelId, но не более 10
            // иначе запрос не выполняется
            /*$idChunks = array_chunk($params[ 'channelId' ], count($slaveList));

            $serverChunks = [];
            foreach ($idChunks as $idChunk)
                foreach ($idChunk as $id => $value)
                    $serverChunks[ $id ][] = $value;*/

            $serverChunks = array_chunk($params[ 'channelId' ], 10);

            $postData = [];
            foreach ($serverChunks as $id => $data)
                $postData[ $id ] = [
                    'method' => $method,
                    'params' => ['channelId' => $data] + $params,
                    'parts' => $parts,
                    'type' => $type,
                    'key' => $validationKey,
                    'apiKeys' => YoutubeAPI::$keys
                ];

            // выполняем в цикле множественный запрос
            $response = [];
            while (count($response) < count($postData)) {
                $urlArray = [];
                $postArray = [];
                $slaveIds = [];

                if (empty($slaveList)) {
                    // если сервера не работают - выполняем запросы самостоятельно
                    foreach ($postData as $id => $data) {
                        if (isset($response[ $id ]))
                            continue;

                        $response[ $id ] = [
                            'result' => gzcompress(serialize(YoutubeAPI::query($data[ 'method' ], $data[ 'params' ], $data[ 'parts' ], $data[ 'type' ]))),
                            'keys' => []
                        ];
                    }

                    break;
                }

                reset($slaveList);
                foreach ($postData as $id => $data) {
                    if (isset($response[ $id ]))
                        continue;

                    $slaveIds[ $id ] = key($slaveList);
                    $urlArray[ $id ] = 'http://' . current($slaveList) . '/';
                    $postArray[ $id ] = $data;

                    if (next($slaveList) === false)
                        break;
                }

                $responsePart = array_map(function($item) {
                    try {
                        $value = unserialize($item);
                        //$value[ 'result' ] = unserialize(gzuncompress($value[ 'result' ]));
                        $value[ 'length'  ] = strlen($item);

                        return $value;
                    } catch (Exception $e) {
                        return false;
                    }
                }, \Yii::$app->curl->queryMultiple($urlArray, $postArray));

                foreach ($responsePart as $id => $value)
                    if (isset($value[ 'result' ])) {
                        $response[ $id ] = $value[ 'result' ];

                        self::$profilingData[] = [
                            'slave_id' => $slaveIds[ $id ],
                            'datetime' => date('Y-m-d H:i:s', $time),
                            'duration' => $value[ 'time' ],
                            'size' => round($value[ 'length' ] / 1024 / 1024, 2),
                            'count' => count($value[ 'result' ]),
                            'type' => $type,
                            'method' => $method,
                            'parts' => implode(',', $parts),
                        ];

                        echo "Время обработки сервером " . $value[ 'ip' ] . ": " . $value[ 'time' ] . " сек. (" . count($value[ 'result' ]) ." значений, объем данных - " .
                            (round($value[ 'length' ] / 1024 / 1024, 2)) . " МБ, #" . $id . "/" . count($postData) . ", память - " .
                            round(memory_get_usage() / 1024 / 1024, 2) . " МБ)\n";

                        foreach ($value[ 'keys' ] as $keyId => $keyData) {
                            if (!$keyData[ 'enabled' ])
                                YoutubeAPI::$keys[ $keyId ][ 'enabled' ] = false;

                            if ($keyData[ 'quota_diff' ] > 0) {
                                YoutubeAPI::$keys[ $keyId ][ 'quota' ] += $keyData[ 'quota_diff' ];
                                YoutubeAPI::$keys[ $keyId ][ 'quota_diff' ] += $keyData[ 'quota_diff' ];
                            }
                        }
                    } else {
                        echo "Сервер " . $slaveList[ $slaveIds[ $id ] ] . "выключен.\n";
                        unset($slaveList[ $slaveIds[ $id ] ]);
                    }
            }

            /*$result = array_reduce($response, function($carry, $item) {
                if (is_array($item))
                    foreach ($item as $value)
                        $carry[] = $value;

                return $carry;
            }, []);*/

            Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                Yii::$app->formatter->asDecimal(YoutubeAPI::getQuotaValue() - $quotaValue) . ', зашифрованных ответов - ' .
                count($response) . ', время - ' .
                Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'highload');

            return $response;
        }

        return false;
    }

    /**
     * Сохранение данных профилировщика.
     */
    public static function saveData()
    {
        if (!empty(self::$profilingData))
            Yii::$app->db->createCommand()->batchInsert(SlaveProfiling::tableName(), array_keys(self::$profilingData[ 0 ]), self::$profilingData)->execute();
    }
}