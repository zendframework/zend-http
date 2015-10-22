<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Http\Header;

use Zend\Http\Header\AcceptLanguage;

class AcceptLanguageTest extends \PHPUnit_Framework_TestCase
{
    public function testAcceptLanguageFromStringCreatesValidAcceptLanguageHeader()
    {
        $acceptLanguageHeader = AcceptLanguage::fromString('Accept-Language: xxx');
        $this->assertInstanceOf('Zend\Http\Header\HeaderInterface', $acceptLanguageHeader);
        $this->assertInstanceOf('Zend\Http\Header\AcceptLanguage', $acceptLanguageHeader);
    }

    public function testAcceptLanguageGetFieldNameReturnsHeaderName()
    {
        $acceptLanguageHeader = new AcceptLanguage();
        $this->assertEquals('Accept-Language', $acceptLanguageHeader->getFieldName());
    }

    public function testAcceptLanguageGetFieldValueReturnsProperValue()
    {
        $acceptLanguageHeader = AcceptLanguage::fromString('Accept-Language: xxx');
        $this->assertEquals('xxx', $acceptLanguageHeader->getFieldValue());
    }

    public function testAcceptLanguageGetFieldValueReturnsProperNonWellFormattedHeaderValue()
    {
        $acceptLanguageHeader = AcceptLanguage::fromString('Accept-Language: en-US;en');
        $this->assertEquals('en-US', $acceptLanguageHeader->getFieldValue());
    }

    public function testAcceptLanguageToStringReturnsHeaderFormattedString()
    {
        $acceptLanguageHeader = new AcceptLanguage();
        $acceptLanguageHeader->addLanguage('da', 0.8)
                             ->addLanguage('en-gb', 1);

        $this->assertEquals('Accept-Language: da;q=0.8, en-gb', $acceptLanguageHeader->toString());
    }

    /** Implementation specific tests here */

    public function testCanParseCommaSeparatedValues()
    {
        $header = AcceptLanguage::fromString('Accept-Language: da;q=0.8, en-gb');
        $this->assertTrue($header->hasLanguage('da'));
        $this->assertTrue($header->hasLanguage('en-gb'));
    }

    public function testPrioritizesValuesBasedOnQParameter()
    {
        $header   = AcceptLanguage::fromString('Accept-Language: da;q=0.8, en-gb, *;q=0.4');
        $expected = [
            'en-gb',
            'da',
            '*'
        ];

        $test = [];
        foreach ($header->getPrioritized() as $type) {
            $this->assertEquals(array_shift($expected), $type->typeString);
        }
        $this->assertEquals($expected, $test);
    }

    public function testWildcharLanguage()
    {
        $acceptHeader = new AcceptLanguage();
        $acceptHeader->addLanguage('da', 0.8)
                     ->addLanguage('*', 0.4);

        $this->assertTrue($acceptHeader->hasLanguage('da'));
        $this->assertTrue($acceptHeader->hasLanguage('en'));
        $this->assertEquals('Accept-Language: da;q=0.8, *;q=0.4', $acceptHeader->toString());
    }

    public function testWildcards()
    {
        $accept = AcceptLanguage::fromString('*, en-*, en-us');
        $res = $accept->getPrioritized();

        $this->assertEquals('en-us', $res[0]->getLanguage());
        $this->assertEquals('en', $res[0]->getPrimaryTag());
        $this->assertEquals('us', $res[0]->getSubTag());

        $this->assertEquals('en-*', $res[1]->getLanguage());
        $this->assertEquals('en', $res[1]->getPrimaryTag());

        $this->assertTrue($accept->hasLanguage('nl'));
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testPreventsCRLFAttackViaFromString()
    {
        $this->setExpectedException('Zend\Http\Header\Exception\InvalidArgumentException');
        $header = AcceptLanguage::fromString("Accept-Language: da\r\n\r\nevilContent");
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testPreventsCRLFAttackViaSetters()
    {
        $header = new AcceptLanguage();
        $this->setExpectedException('Zend\Http\Header\Exception\InvalidArgumentException', 'valid type');
        $header->addLanguage("\nen\r-\r\nus");
    }
}
