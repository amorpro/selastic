<?php

scenario(function($q, $exception){
    $expectedException = false;
    try{
        $client = new \Selastic\Client('http://es.com');
        $client->search($q);
    }catch (Exception $e){
        $expectedException = get_class($e) === $exception;
    }
    it('expect the invalid argument exception', $expectedException);

},[
    'empty query' => [ null, InvalidArgumentException::class ],
    'empty array' => [ [], InvalidArgumentException::class ],
    'empty json' => [ '{}', InvalidArgumentException::class ],
    'wrongly formatted json' => [ '{"aa":1,}', InvalidArgumentException::class ]
] );

scenario(function($q){
    try{
        $client = new \Selastic\Client('http://12jh3jh2g34jh23g4.com');
        $client->search($q);
    }catch (Exception $e){
        it('expect no issues with query string', ($e instanceof \Selastic\Exception\RequestFailed));
    }


},[
    'string query' => [ 'user:11'],
    'array query' => [ ['user' => 11] ],
    'json query' => [ '{"user":11}'],
] );