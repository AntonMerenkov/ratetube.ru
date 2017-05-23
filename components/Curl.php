<?php

namespace app\components;
use MCurl\Client;

/**
 * Class Curl
 *
 * Загрузка данных по HTTP с использованием curl.
 */
class Curl
{
    private $curl;

    private $curlMulti;
    private $curlMultiHandles = [];

    private function setCurlDefaultParams($curl)
    {
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.7.62 Version/11.01');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // не проверять сертификат HTTPS
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    }

    /**
     * Curl constructor.
     *
     * Установка начальных параметров curl.
     */
    public function __construct()
    {
        $this->curl = curl_init();

        $this->setCurlDefaultParams($this->curl);
    }

    /**
     * Одиночный запрос.
     *
     * @param $url
     * @return mixed
     */
    public function querySingle($url)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        return curl_exec($this->curl);
    }

    /**
     * Множественный запрос.
     *
     * @param $urlArray
     * @return array
     */
    public function queryMultiple($urlArray)
    {
        return $this->queryMultipleCurlMulti($urlArray);
    }

    /**
     * Множественный запрос - выполнение одного запроса в цикле.
     *
     * @param $urlArray
     * @return array
     */
    private function queryMultipleCycle($urlArray)
    {
        $response = [];

        foreach ($urlArray as $url)
            $response[] = $this->querySingle($url);

        return $response;
    }

    /**
     * Множественный запрос - curl_multi.
     *
     * @param $urlArray
     * @return array
     */
    private function queryMultipleCurlMulti($urlArray)
    {
        $response = [];

        $this->curlMulti = curl_multi_init();
        $this->curlMultiHandles = [];

        foreach ($urlArray as $url) {
            $curl = curl_init();

            $this->setCurlDefaultParams($curl);
            curl_setopt($curl, CURLOPT_URL, $url);

            curl_multi_add_handle($this->curlMulti, $curl);

            $this->curlMultiHandles[] = $curl;
        }

        $running = null;
        do {
            curl_multi_exec($this->curlMulti, $running);
        } while ($running > 0);

        foreach ($this->curlMultiHandles as $channel) {
            $response[] = curl_multi_getcontent($channel);
            curl_multi_remove_handle($this->curlMulti, $channel);
        }

        curl_multi_close($this->curlMulti);

        return $response;
    }

    /**
     * Множественный запрос - mcurl.
     *
     * @param $urlArray
     * @return array
     */
    private function queryMultipleMCurl($urlArray)
    {
        $client = new Client();

        foreach ($urlArray as $url) {
            $client->add([CURLOPT_URL => $url]);
        }

        return $client->all();
    }
}