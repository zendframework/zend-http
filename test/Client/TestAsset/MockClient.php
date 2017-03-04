<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

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
        'outputstream'    => false,
        'encodecookies'   => true,
    ];
}
