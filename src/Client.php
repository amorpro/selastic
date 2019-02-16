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
     * Do the search request to Elastic Search.
     *
     * @example
     *
     * Base search request
     *
     * $client->search('hours:>2 AND user:"Anton Butkov"')
     *
     * Aggregation search request via json
     *
     * $client->search('{
     *   'query' : {...},
     *   'aggs' : {...}
     * }');
     *
     *
     * Aggregation search request via array
     *
     * $client->search([
     *   'query' => [...],
     *   'aggs' => [...]
     * ]);
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

            $q = json_encode($q);
            if(!$q){
                throw new \InvalidArgumentException('Query array has wrong format to be json_encoded');
            }
        }

        if($this->_isJson($q)){
            $decodedQuery = json_decode($q);
            if(!$decodedQuery){
                throw new \InvalidArgumentException('Json query has wrong format and can\'t be decoded');
            }

            if(!count((array)$decodedQuery)){
                throw new \InvalidArgumentException('Json query has can\'t be with empty body');
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
            sprintf('%s/%s/_search?q=%s', $this->apiUrl, urlencode($this->index), urlencode($q)) :
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