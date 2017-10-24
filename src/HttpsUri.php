<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Http;

use Zend\Uri\Exception as UriException;
use Zend\Uri\Http as Http;

class HttpsUri extends Http
{
    /**
     * Get the scheme part of the URI
     *
     * @return string|null
     */
    public function getScheme()
    {
        return 'https';
    }
}

