<?php

namespace Hungnguyen\Curl;

use Hungnguyen\Curl\CurlHelper;

class Curl
{
    use CurlHelper;

    /**
     * Flag to mark that client service should get meta data when call API
     *
     * @var boolean
     */
    protected $isGetMetaData = false;

    /**
     * Enable $isGetMetaData flag
     *
     * @return $this
     */
    public function enableMetaData()
    {
        $this->isGetMetaData = true;
        return $this;
    }

     /**
     * Get metadata flag and reset to false
     *
     * @return boolean
     */
    protected function getAndResetMetaDataFlag() : bool
    {
        $isGetMetaData = $this->isGetMetaData;
        $this->isGetMetaData = false;
        return $isGetMetaData;
    }


    /**
     * Create a new Curl instance.
     *
     * @param  string  $url
     * @return void
     */
    public function __construct(string $url = '')
    {
        $this->setUrl($url);
    }

    /**
     * Get the target url
     *
     * @var string
     */
    public function getUrl() : string
    {
        return $this->url ?? '';
    }

    /**
     * Set the target url
     *
     * @return static
     */
    public function setUrl(string $url) : Curl
    {
        $this->url = empty($url) ? with(new SystemVariable)->apiHost() : $url;
        return $this;
    }

    /**
     * Check whether the response is success or not
     *
     * @return bool
     */
    public function success() : bool
    {
        return ($this->httpStatusCode >= 200 && $this->httpStatusCode < 300);
    }

    /**
     * Excecute the GET request and get the response body as array
     *
     * @return array
     */
    public function getRecord(string $api, int $id, array $input = []) : array
    {
        return $this->get($api . '/' . $id, $input);
    }

    /**
     * Excecute the GET request and get the response body as array
     *
     * @return array
     */
    public function get(string $api, array $input = [], $isGetMetaData = false) : array
    {
        if ($isGetMetaData) {
            $this->enableMetaData();
        }

        $query = '?' . $this->buildQuerry($input);
        if (strlen($query) < 1500) {
            $ch = curl_init($this->getUrl() . $api . $query);
            $response = $this->exec($ch);
            return $this->getAndResetMetaDataFlag() ? $response : array_get($response, 'data', $response);
        } else {
            $input['isGet'] = 1;
            return $this->post($api, $input);
        }
    }

    /**
     * Excecute the POST request and get the response body as array
     *
     * @return array
     */
    public function post(string $api, array $input) : array
    {
        $ch = curl_init($this->getUrl() . $api);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->filter($input)));
        return $this->exec($ch, 2) ?? [];
    }

    /**
     * Excecute the PUT request and get the response body as array
     *
     * @return array
     */
    public function put(string $api, string $ids, array $input) : array
    {
        $ch = curl_init($this->getUrl() . $api . '/' . $ids);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->filter($input)));
        return $this->exec($ch, 3);
    }

    /**
     * Excecute the DELETE request
     *
     * @return void
     */
    public function delete($api, int $id) : void
    {
        $ch = curl_init($this->getUrl() . $api . '/' . $id);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->exec($ch, 4);
    }

    /**
     * Excecute request and get the response body as array
     *
     * @return array
     */
    protected function exec($ch, int $type = 1) : array
    {
        $this->httpStatusCode = 0;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeader($type));
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($ch);
        $this->setStatusCode((int) curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        return $this->success() ? (json_decode($response, true) ?? []) : [];
    }
}
