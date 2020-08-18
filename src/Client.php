<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 15.02.2019
 * Time: 23:45
 */

namespace Selastic;


use Selastic\Exception\RequestFailed;
use Selastic\Helper\Curl;

class Client
{
    const INDEX_ALL = '_all';

    /**
     * Full url to ElasticSearch API
     * @var
     */
    private $apiUrl;

    /**
     * Index that will be used for search requests
     * @var string
     */
    private $index = self::INDEX_ALL;

    /**
     * @var int
     */
    private $from = 0;

    /**
     * @var int
     */
    private $size = 10;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * Client constructor.
     * @param string $apiUrl Full url to ElasticSearch API
     * @param string $index index that will be used
     */
    public function __construct($apiUrl, $index = self::INDEX_ALL)
    {
        $this->_assertApiReachable($apiUrl);
        $this->apiUrl = $apiUrl;
        $this->useIndex($index);
        $this->curl = new Curl();
    }

    /**
     * @param $index
     * @return $this
     */
    public function useIndex($index)
    {
        if(!$index){
            throw new \InvalidArgumentException('Index can\'t be empty');
        }

        $this->index = $index;
        return $this;
    }

    /**
     * Starting document offset. Default 0
     *
     * @param int $from
     * @return $this
     */
    public function setFrom($from)
    {
        $this->from = (int)$from;
        return $this;
    }

    /**
     * Number of document to return. Default 10
     *
     * @param int $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = (int)$size;
        return $this;
    }



    /**
     * Do the search request to Elastic Search.
     *
     * @example
     *
     * Base search request
     *
     * $client->search('hours:>2 AND user:"Anton Butkov"')
     *
     * Aggregation search request via array
     *
     * $client->search([
     *   'query' => [...],
     *   'aggs' => [...]
     * ]);
     *
     * result example
     * [
            'took' => 115,
            'timed_out' => false,
            '_shards' => [
                'total' => 693,
                'successful' => 693,
                'failed' => 0
            ],
            'hits' => [
                'total' => 63152,
                'max_score' => 10.24,
                'hits' => [
                    // array of result documents
                ]
            ]
       ]
     *
     * @param string|array|json $q
     * @return array
     * @throws RequestFailed
     */
    public function search($q)
    {
        if(!$q){
            throw new \InvalidArgumentException('Query string can\'t be empty');
        }

        if (is_array($q)) {
            if(!count($q)){
                throw new \InvalidArgumentException('Query can\'b be an empty array');
            }

            if(!isset($q['query'])){
                $q['query'] = $q;
            }
            $q['from'] = $this->from;
            $q['size'] = $this->size;

            $q = json_encode($q);
            if(!$q){
                throw new \InvalidArgumentException('Query array has wrong format to be json_encoded');
            }

            return $this->_searchDo($this->_buildSearchUrl(), $q);
        }

        return $this->_searchDo($this->_buildSearchUrl($q));
    }

    /**
     * @param $url
     * @param null $q
     * @return mixed
     * @throws RequestFailed
     */
    private function _searchDo($url, $q = null)
    {
        $curlOptions = [];
        $curlOptions[CURLOPT_URL]            = $url;
        $curlOptions[CURLOPT_RETURNTRANSFER] = true;
        if ($q) {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'POST';
            $curlOptions[CURLOPT_POSTFIELDS]    = $q;
            $curlOptions[CURLOPT_HTTPHEADER]    = $this->_getAdditionalElasticHeaders($url);
        }

        // REQUEST
        $response = $this->curl->execute($curlOptions);
        return json_decode($response, true);

    }

    /**
     * @param $q
     * @return string
     */
    protected function _buildSearchUrl($q = null)
    {
        return $q ?
            sprintf('%s/%s/_search?from=%s&size=%s&q=%s', $this->apiUrl, urlencode($this->index), $this->from, $this->size, urlencode($q)) :
            sprintf('%s/%s/_search', $this->apiUrl, urlencode($this->index));
    }

    /**
     * @param $url
     * @return array
     * @throws RequestFailed
     */
    private function _getAdditionalElasticHeaders($url)
    {
        $elasticHeaders = $this->curl->options($url);
        $additionalHeaders = array_diff_key($elasticHeaders, array_fill_keys(Curl::HEADER_NAMES, null));

        $additionalHeaderStrings = [];
        foreach ($additionalHeaders as $header => $value) {
            $additionalHeaderStrings[] = "$header: $value";
        }
        return $additionalHeaderStrings;
    }

    /**
     * @param $q
     * @return false|int
     */
    private function _isJson($q)
    {
        return preg_match('/\{.*\}/', $q);
    }

    private function _assertApiReachable($apiUrl)
    {
        if(!filter_var($apiUrl, FILTER_VALIDATE_URL)){
            throw new \InvalidArgumentException('Invalid API url');
        }
    }

}