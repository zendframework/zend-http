<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Http\Exception;

class UnexpectedValueException extends \UnexpectedValueException implements ExceptionInterface
{
    /**
     * @param string $expected
     * @param mixed $actual
     *
     * @return UnexpectedValueException
     */
    public static function unexpectedType($expected, $actual)
    {
        return new static(sprintf(
            'Expected %s. %s given',
            $expected,
            is_object($actual) ? get_class($actual) : gettype($actual)
        ));
    }
}
