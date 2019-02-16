<?php

scenario(function($api, $index, $exception){
    $expectedException = false;
    try{
        $client = new \Selastic\Client($api, $index);
    }catch (Exception $e){
        $expectedException = get_class($e) === $exception;
    }
    it('expect the invalid argument exception', $expectedException);

},[
    'null api' => [ null, '_all', InvalidArgumentException::class ],
    'empty api' => [ '', '_all', InvalidArgumentException::class ],
    'api is not valid url' => [ 'aa', '_all', InvalidArgumentException::class ],
    'null index' => [ 'http://es.com', null, InvalidArgumentException::class ],
    'empty index2' => [ 'http://es.com', '', InvalidArgumentException::class ],
] );