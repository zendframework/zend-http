<?php

namespace ZendTest\Http\Exception;

use ArrayObject;
use Zend\Http\Exception\UnexpectedValueException;
use PHPUnit\Framework\TestCase;

class UnexpectedValueExceptionTest extends TestCase
{
    public function testUnexpectedTypeWithObjectType()
    {
        $object = new ArrayObject();
        $exception = UnexpectedValueException::unexpectedType('foo', $object);

        $this->assertSame('Expected foo. ArrayObject given', $exception->getMessage());
    }

    public function testUnexpectedTypeWithScalarType()
    {
        $exception = UnexpectedValueException::unexpectedType('foo', 5);

        $this->assertSame('Expected foo. integer given', $exception->getMessage());
    }
}
