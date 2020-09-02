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
    private $offset = 0;

    /**
     * @var int
     */
    private $limit = 10000;

    /**
     * @var \DateTime
     */
    private $timeStampFrom;

    /**
     * @var \DateTime
     */
    private $timestampTo;

    /**
     * @var []
     */
    private $columns;

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
        $this->from($index);
        $this->curl = new Curl();
    }

    private function _assertApiReachable($apiUrl)
    {
        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid API url');
        }
    }

    /**
     * @param $index
     * @return $this
     */
    public function from($index)
    {
        if(is_array($index)){
            $index = implode($index, ',');
        }
        if (!$index) {
            throw new \InvalidArgumentException('Index can\'t be empty');
        }

        $this->index = $index;
        return $this;
    }

    /**
     * @return mixed
     * @throws array
     */
    public function getIndexes()
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/common-options.html
        return $this->_searchDo(sprintf('%s/_cat/indices/%s?format=json&s=index', $this->apiUrl, $this->index));

    }

    /**
     * @param $url
     * @param null $q
     * @return mixed
     * @throws RequestFailed
     */
    private function _searchDo($url)
    {
        $curlOptions                         = [];
        $curlOptions[CURLOPT_URL]            = $url;
        $curlOptions[CURLOPT_RETURNTRANSFER] = true;

        // REQUEST
        $response = $this->curl->execute($curlOptions);
        $result   = json_decode($response, true);

        if (isset($result['error'])) {
            throw new \RuntimeException($result['error']['reason']);
        }
        return $result;

    }

    /**
     * Starting document offset. Default 0
     *
     * @param int $from
     * @return $this
     */
    public function offset($from)
    {
        $this->offset = (int)$from;
        return $this;
    }

    /**
     * Number of document to return. Default 10
     *
     * @param int $size
     * @return $this
     */
    public function limit($size)
    {
        $this->limit = (int)$size;
        return $this;
    }

    /**
     * @param $timestamp
     * @return $this
     * @throws \Exception
     */
    public function filterByCreatedFrom($timestamp)
    {
        $dateTime = new \DateTime('now');
        $dateTime->setTimestamp($timestamp);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        $this->timeStampFrom = $dateTime;

        return $this;
    }

    /**
     * @param $timestamp
     * @return $this
     * @throws \Exception
     */
    public function filterByCreatedTo($timestamp)
    {
        $dateTime = new \DateTime('now');
        $dateTime->setTimestamp($timestamp);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        $this->timestampTo = $dateTime;
        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function select(array $columns)
    {
        if (!in_array(Document::ID, $columns, true)) {
            $columns[] = Document::ID;
        }

        $columns[] = Document::CREATED;

        $columns = array_map(function ($column) {
            return $column === Document::ID ?
                'hits.hits.' . $column :
                'hits.hits._source.' . $column;
        }, $columns);

        $columns[] = 'hits.total';
        $columns[] = 'took';
        $columns[] = 'timed_out';
        $columns[] = '_shards';

        $this->columns = $columns;

        return $this;
    }

    /**
     * Do the search request to Elastic Search.
     *
     * @param string|array|json $q
     * @return Response
     * @throws RequestFailed
     * @example
     *
     * Base search request
     *
     * $client->search('hours:>2 AND user:"Anton Butkov"')
     *
     * result example
     * [
     * 'took' => 115,
     * 'timed_out' => false,
     * '_shards' => [
     * 'total' => 693,
     * 'successful' => 693,
     * 'failed' => 0
     * ],
     * 'hits' => [
     * 'total' => 63152,
     * 'max_score' => 10.24,
     * 'hits' => [
     * // array of result documents
     * ]
     * ]
     * ]
     *
     */
    public function search($q)
    {
        if (!$q) {
            throw new \InvalidArgumentException('Query string can\'t be empty');
        }

        if($this->timeStampFrom && $this->timestampTo){
            $q .= sprintf(' AND @timestamp:[%s TO %s]', $this->timeStampFrom->format('Y-m-d\TH:i:s'), $this->timestampTo->format('Y-m-d\TH:i:s'));
        }

        return new Response($this->_searchDo($this->_buildSearchUrl($q)));
    }

    /**
     * @param $q
     * @return string
     */
    protected function _buildSearchUrl($q = null)
    {
        return sprintf('%s/%s/_search?format=json&filter_path=%s&from=%s&size=%s&q=%s', $this->apiUrl, urlencode($this->index), $this->_getColumns(), $this->offset, $this->limit, urlencode($q));
    }

    /**
     * @return string
     */
    protected function _getColumns(): string
    {
        if (!$this->columns) {
            return '*';
        }

        return implode(',', $this->columns);
    }

}