<?php

header('Content-Type:text/plain;charset=utf-8');
error_reporting(E_ALL && ~E_NOTICE);

$apiKey = 'AIzaSyBqjwhOuFPLEq6oDe4agB9M4YHJtMGxa8A';
$requestCycles = 1;

$usersIds = array(
    'Itinvest',
    'theunitedtraders'
);

$fullTime = microtime(true);

echo "User channels: " . count($usersIds) * $requestCycles . "\n";

$cUrl = curl_init();
curl_setopt($cUrl, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($cUrl, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.7.62 Version/11.01');
curl_setopt($cUrl, CURLOPT_SSL_VERIFYPEER, 0); // не проверять сертификат HTTPS
curl_setopt($cUrl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($cUrl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

function curl_get_contents($url)
{
	global $cUrl;
	curl_setopt($cUrl, CURLOPT_URL, $url);
	$res = curl_exec($cUrl);
	
	if (!$res)
		die('Ошибка ' . curl_errno($cUrl) . ': ' . curl_error($cUrl));
		
	return $res;
}

/**
 * Получаем id каналов
 */
$time = microtime(true);
$channelIds = array();
for ($i = 1; $i <= $requestCycles; $i++)
    foreach ($usersIds as $userId) {
        $result = json_decode(curl_get_contents('https://www.googleapis.com/youtube/v3/channels?' . http_build_query(array(
            'part' => 'id',
            'forUsername' => $userId,
            'key' => $apiKey
        ))), true);

        if (!empty($result[ 'items' ]))
            $channelIds = array_merge($channelIds, array_map(function($item) {
                return $item[ 'id' ];
            }, $result[ 'items' ]));
    }

echo "\n";
echo "Fetching channels...\n";
echo 'Channels: ' . count($channelIds) . "\n";
echo 'Queries: ' . count($usersIds) * $requestCycles . ' (' .
    number_format((microtime(true) - $time) / (count($usersIds) * $requestCycles), 2, ',', '') .  "s per one)\n";
echo 'Time: ' . number_format(microtime(true) - $time, 2, ',', '') . "s\n";
echo "\n";

/**
 * Получаем id видео
 */
$time = microtime(true);
$videoIds = array();
$queryCount = 0;
foreach ($channelIds as $channelId) {
    $nextPageToken = '';

    do {
        $result = json_decode(curl_get_contents('https://www.googleapis.com/youtube/v3/search?' . http_build_query(array(
            'part' => 'snippet',
            'channelId' => $channelId,
            'maxResults' => 50,
            'type' => 'video',
            'order' => 'viewCount',
            'key' => $apiKey
        ) + ($nextPageToken != '' ? array('pageToken' => $nextPageToken) : array()))), true);

        $queryCount++;

        if (!empty($result[ 'items' ]))
            $videoIds = array_merge($videoIds, array_combine(array_map(function($item) {
                return $item[ 'id' ][ 'videoId' ];
            }, $result[ 'items' ]), array_map(function($item) {
                return array(
                    'id' => $item[ 'id' ][ 'videoId' ],
                    'title' => $item[ 'snippet' ][ 'title' ]
                );
            }, $result[ 'items' ])));
        else
            break;

        $nextPageToken = $result[ 'nextPageToken' ];
        $i++;
    } while ($nextPageToken != '');
}

echo "Fetching videos...\n";
echo 'Videos: ' . count($videoIds) . "\n";
echo 'Queries: ' . $queryCount . ' (' .
    number_format((microtime(true) - $time) / ($queryCount), 2, ',', '') .  "s per one)\n";
echo 'Time: ' . number_format(microtime(true) - $time, 2, ',', '') . "s\n";
echo "\n";

/**
 * Получаем статистику
 */
$time = microtime(true);
foreach (array_chunk($videoIds, 50) as $videoIdsChunk) {
    $result = json_decode(curl_get_contents('https://www.googleapis.com/youtube/v3/videos?' . http_build_query(array(
            'part' => 'statistics',
            'maxResults' => 50,
            'id' => implode(',', array_map(function($item) {
                return $item[ 'id' ];
            }, $videoIdsChunk)),
            'key' => $apiKey
        ))), true);

    foreach ($result[ 'items' ] as $item)
        if (isset($videoIds[ $item[ 'id' ] ]))
            $videoIds[ $item[ 'id' ] ][ 'statistics' ] = $item[ 'statistics' ];
}

echo "Fetching statistics...\n";
echo 'Queries: ' . ceil(count($videoIds) / 50) . ' (' .
    number_format((microtime(true) - $time) / ceil(count($videoIds) / 50), 2, ',', '') .  "s per one)\n";;
echo 'Time: ' . number_format(microtime(true) - $time, 2, ',', '') . "s\n";

echo "\n";
echo 'Total time: ' . number_format(microtime(true) - $fullTime, 2, ',', '') . "s\n";

curl_close($cUrl);

// TODO: https://php.ru/manual/function.curl-multi-init.html