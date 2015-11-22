<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Http\Client\Adapter\Exception;

/**
 * Connection timeout exceptions occur when the adapter exceeds the specified time limit
 * to connect to a host:port, whereas timeout exceptions occur when the adapter exceeds the time
 * limit to complete an operation.
 */
class ConnectTimeoutException extends RuntimeException implements ExceptionInterface
{
}
