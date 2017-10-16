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
     * @param array $post
     * @return mixed
     */
    public function querySingle($url, $post = [])
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);

        if (!empty($post)) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($post));
        } else {
            curl_setopt($this->curl, CURLOPT_POST, false);
        }

        $data = curl_exec($this->curl);
        //print_r(curl_getinfo($this->curl));

        return $data;
    }

    /**
     * Множественный запрос.
     *
     * @param $urlArray
     * @param array $post
     * @return array
     */
    public function queryMultiple($urlArray, $post = [])
    {
        return $this->queryMultipleCurlMulti($urlArray, $post);
    }

    /**
     * Множественный запрос - выполнение одного запроса в цикле.
     *
     * @param $urlArray
     * @param array $post
     * @return array
     */
    private function queryMultipleCycle($urlArray, $post = [])
    {
        $response = [];

        foreach ($urlArray as $id => $url)
            $response[ $id ] = $this->querySingle($url, isset($post[ $id ]) ? $post[ $id ] : []);

        return $response;
    }

    /**
     * Множественный запрос - curl_multi.
     *
     * @param $urlArray
     * @param array $post
     * @return array
     */
    private function queryMultipleCurlMulti($urlArray, $post = [])
    {
        $response = [];

        $this->curlMulti = curl_multi_init();
        $this->curlMultiHandles = [];

        foreach ($urlArray as $id => $url) {
            $curl = curl_init();

            $this->setCurlDefaultParams($curl);
            curl_setopt($curl, CURLOPT_URL, $url);

            if (!empty($post[ $id ])) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post[ $id ]));
                curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            }

            curl_multi_add_handle($this->curlMulti, $curl);

            $this->curlMultiHandles[ $id ] = $curl;
        }

        curl_multi_setopt($this->curlMulti, CURLMOPT_PIPELINING, 3);

        $running = null;
        do {
            curl_multi_exec($this->curlMulti, $running);
            curl_multi_select($this->curlMulti);
        } while ($running > 0);

        foreach ($this->curlMultiHandles as $id => $channel) {
            $response[ $id ] = curl_multi_getcontent($channel);
            curl_multi_remove_handle($this->curlMulti, $channel);
        }

        curl_multi_close($this->curlMulti);

        return $response;
    }

    /**
     * Множественный запрос - mcurl.
     *
     * @param $urlArray
     * @param array $post
     * @return array
     */
    private function queryMultipleMCurl($urlArray, $post = [])
    {
        $client = new Client();

        foreach ($urlArray as $id => $url) {
            if (isset($post[ $id ]))
                $client->add([CURLOPT_URL => $url], [
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $post[ $id ]
                ]);
            else
                $client->add([CURLOPT_URL => $url]);
        }

        return array_map(function($item) {
            return $item->getBody();
        }, $client->all());
    }
}