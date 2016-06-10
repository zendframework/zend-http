<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Http\Header;

use Zend\Http\Header\ContentLength;

class ContentLengthTest extends \PHPUnit_Framework_TestCase
{
    public function testFromStringCreatesValidContentLengthHeader()
    {
        $contentLengthHeader = ContentLength::fromString('Content-Length: 123');
        $this->assertInstanceOf('Zend\Http\Header\HeaderInterface', $contentLengthHeader);
        $this->assertInstanceOf('Zend\Http\Header\ContentLength', $contentLengthHeader);
    }

    public function testGetFieldNameReturnsHeaderName()
    {
        $contentLengthHeader = new ContentLength('123');
        $this->assertEquals('Content-Length', $contentLengthHeader->getFieldName());
    }

    public function testGetFieldValueReturnsProperValue()
    {
        $contentLengthHeader = new ContentLength('123');
        $this->assertEquals('123', $contentLengthHeader->getFieldValue());
    }

    public function testToStringReturnsHeaderFormattedString()
    {
        $contentLengthHeader = new ContentLength('12345');
        $this->assertSame('Content-Length: 12345', $contentLengthHeader->toString());
    }

    public function testCastValueToString()
    {
        $contentLengthHeader = new ContentLength(12345);
        $this->assertEquals('12345', $contentLengthHeader->getFieldValue());
    }

    public function testAllowZeroValue()
    {
        $contentLengthHeader = new ContentLength('0');
        $this->assertEquals('0', $contentLengthHeader->getFieldValue());
    }

    public function testAllowBigIntValue()
    {
        $power95of2 = '39614081257132168796771975168';
        $contentLengthHeader = new ContentLength($power95of2);
        $this->assertEquals($power95of2, $contentLengthHeader->getFieldValue());
    }

    /**
     * @dataProvider invalidValues
     */
    public function testInvalidValueThrowsExceptionOnConstructor($invalidValue)
    {
        $this->setExpectedException('Zend\Http\Header\Exception\InvalidArgumentException');
        new ContentLength($invalidValue);
    }

    /**
     * @dataProvider invalidValues
     */
    public function testInvalidValueThrowsExceptionOnSetFieldValue($invalidValue)
    {
        $this->setExpectedException('Zend\Http\Header\Exception\InvalidArgumentException');
        $header = new ContentLength('123');
        $header->setFieldValue($invalidValue);
    }

    /**
     * Data provider of invalid values
     * @return array
     */
    public function invalidValues()
    {
        return [
            // invalid number
            ['abc'],
            ['1a1'],
            ['a1a'],

            // invalid integer
            ['123.456'],
            ['0x12'],
            ['xFF'],
            [' 1 '],

            // http://en.wikipedia.org/wiki/HTTP_response_splitting
            ['123\r\n\r\nevilContent'],
        ];
    }
}
