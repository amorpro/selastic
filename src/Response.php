<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 20.08.2020
 * Time: 15:41
 */

namespace Selastic;


class Response
{

    private $raw = [];

/*array(4) {
["took"]=>
int(67)
["timed_out"]=>
bool(false)
["_shards"]=>
array(3) {
["total"]=>
int(60)
["successful"]=>
int(60)
["failed"]=>
int(0)
}
["hits"]=>
  array(3) {
    ["total"]=>
    int(18571)
    ["max_score"]=>
    float(5.20868)
    ["hits"]=>
    array(1) {
        [0]=>
      array(5) {
            ["_index"]=>
        string(24) "web-analytics-2020.08.18"
            ["_type"]=>
        string(5) "event"
            ["_id"]=>
        string(20) "AXP-ea06Mrxt91vAJ97f"
            ["_score"]=>
        float(5.20868)
        ["_source"]=>
        array(16) {
                ["hostname"]=>
          string(11) "pornbox.com"
                ["screenResolution"]=>
          string(7) "375x812"
                ["userAgent"]=>
          string(139) "Mozilla/5.0 (iPhone; CPU iPhone OS 13_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.2 Mobile/15E148 Safari/604.1"
                ["trackingId"]=>
          string(8) "BOX-PROD"
                ["clientId"]=>
          string(10) "xaaynkcchn"
                ["sessionId"]=>
          string(10) "t3q8urd058"
                ["userId"]=>
          string(12) "intotheether"
                ["country_id"]=>
          string(3) "840"
                ["eventCategory"]=>
          string(5) "store"
                ["eventAction"]=>
          string(6) "filter"
                ["eventLabel"]=>
          string(6) "studio"
                ["eventValue"]=>
          string(14) "Giorgio Grandi"
                ["hitType"]=>
          string(5) "event"
                ["remote_addr"]=>
          string(13) "73.148.141.27"
                ["host"]=>
          string(22) "analytics.gtflixtv.com"
                ["@timestamp"]=>
          string(24) "2020-08-17T22:12:57.376Z"
        }
      }
    }
  }
}*/


    /**
     * Response constructor.
     * @param array $raw
     */
    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    /**
     * @return int
     */
    public function getTotalDocuments()
    {
        return (int)$this->raw['hits']['total'];
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->raw['hits']['hits']['_index'];
    }

    /**
     * @return Document[]
     */
    public function getDocuments()
    {
        $documents = [];
        $hits = $this->raw['hits']['hits'] ?? [];
        foreach($hits as $hit){
            $document = $hit['_source'];
            $document[Document::ID] = $hit[Document::ID];

            $documents[] = new Document($document);
        }

        return $documents;
    }



}