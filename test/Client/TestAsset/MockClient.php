<?php

namespace ZendTest\Http\Client\TestAsset;

use Zend\Http\Request;

class MockClient extends \Zend\Http\Client
{
    public $config = [
        'maxredirects'    => 5,
        'strictredirects' => false,
        'useragent'       => 'Zend_Http_Client',
        'timeout'         => 10,
        'adapter'         => 'Zend\\Http\\Client\\Adapter\\Socket',
        'httpversion'     => Request::VERSION_11,
        'keepalive'       => false,
        'storeresponse'   => true,
        'strict'          => true,
        'outputstream'   => false,
        'encodecookies'   => true,
    ];
}
