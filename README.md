# Selastic

Simple client for ElasticSearch. Helps to make simple search or aggregate queries.

## Examples

```php
   
$client = new \Selastic\Client('http://yourelasticnode.com');
// Not requared, by default will be used '_all'
$client->useIndex('analytics')

// Simple string query
$result = $client->search('user:12');

// Array query
$result = $client->search(['user' => 12]);

// Json query
$result = $client->search('{ "user" : 12 }');

// Array aggregation query
$result = $client->search([ 'query' => [...], 'aggs' => [...] ]);

// Json aggregation query
$result = $client->search('{"query" : {...}, "aggs" => {...}}');

```