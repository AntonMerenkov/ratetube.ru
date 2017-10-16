<?php

namespace app\components;
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
                        $uncompressed = gzuncompress($response);
                        $value = json_decode($uncompressed, true) + [ 'length' => strlen($response) ];
                        $result = $value[ 'result' ];

                        echo "Время обработки сервером " . $value[ 'ip' ] . ": " . $value[ 'time' ] . " сек. (объем данных - " . (round($value[ 'length' ] / 1024 / 1024, 2)) . " МБ)\n";

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
                Yii::$app->formatter->asDecimal(YoutubeAPI::getQuotaValue() - $quotaValue) . ', время - ' .
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
                            'result' => YoutubeAPI::query($data[ 'method' ], $data[ 'params' ], $data[ 'parts' ], $data[ 'type' ]),
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

                $qTime = microtime(true);
                $responsePart = array_map(function($item) {
                    try {
                        $uncompressed = gzuncompress($item);
                        return json_decode($uncompressed, true) + [ 'length' => strlen($item) ];
                    } catch (Exception $e) {
                        return false;
                    }
                }, \Yii::$app->curl->queryMultiple($urlArray, $postArray));
                echo "Запрос: " . round(microtime(true) - $qTime, 2) . " сек.\n";

                foreach ($responsePart as $id => $value)
                    if (isset($value[ 'result' ])) {
                        $response[ $id ] = $value[ 'result' ];

                        echo "Время обработки сервером " . $value[ 'ip' ] . ": " . $value[ 'time' ] . " сек. (" . count($value[ 'result' ]) ." значений, объем данных - " . (round($value[ 'length' ] / 1024 / 1024, 2)) . " МБ)\n";

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

            $result = array_reduce($response, function($carry, $item) {
                foreach ($item as $value)
                    $carry[] = $value;

                return $carry;
            }, []);

            Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                Yii::$app->formatter->asDecimal(YoutubeAPI::getQuotaValue() - $quotaValue) . ', время - ' .
                Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'highload');

            return $result;
        } else if ($type == YoutubeAPI::QUERY_PAGES) {
            // разделяем запросы на несколько серверов по channelId
            $idChunks = array_chunk($params[ 'channelId' ], count($slaveList));

            $serverChunks = [];
            foreach ($idChunks as $idChunk)
                foreach ($idChunk as $id => $value)
                    $serverChunks[ $id ][] = $value;

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
                            'result' => YoutubeAPI::query($data[ 'method' ], $data[ 'params' ], $data[ 'parts' ], $data[ 'type' ]),
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

                $qTime = microtime(true);
                $responsePart = array_map(function($item) {
                    try {
                        $uncompressed = gzuncompress($item);
                        return json_decode($uncompressed, true) + [ 'length' => strlen($item) ];
                    } catch (Exception $e) {
                        return false;
                    }
                }, \Yii::$app->curl->queryMultiple($urlArray, $postArray));
                echo "Запрос: " . round(microtime(true) - $qTime, 2) . " сек.\n";

                foreach ($responsePart as $id => $value)
                    if (isset($value[ 'result' ])) {
                        $response[ $id ] = $value[ 'result' ];

                        echo "Время обработки сервером " . $value[ 'ip' ] . ": " . $value[ 'time' ] . " сек. (" . count($value[ 'result' ]) ." значений, объем данных - " . (round($value[ 'length' ] / 1024 / 1024, 2)) . " МБ)\n";

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

            $result = array_reduce($response, function($carry, $item) {
                foreach ($item as $value)
                    $carry[] = $value;

                return $carry;
            }, []);

            Yii::info('Выполнен запрос "' . $method . '", использовано квот - ' .
                Yii::$app->formatter->asDecimal(YoutubeAPI::getQuotaValue() - $quotaValue) . ', время - ' .
                Yii::$app->formatter->asDecimal(microtime(true) - $time, 2) . ' сек', 'highload');

            return $result;
        }

        return false;
    }
}