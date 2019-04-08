<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Http\Header;

use PHPUnit\Framework\TestCase;
use Zend\Http\Header\FeaturePolicy;
use Zend\Http\Header\Exception\InvalidArgumentException;
use Zend\Http\Header\HeaderInterface;

class FeaturePolicyTest extends TestCase
{
    public function testFeaturePolicyFromStringThrowsExceptionIfImproperHeaderNameUsed()
    {
        $this->expectException(InvalidArgumentException::class);
        FeaturePolicy::fromString('X-Feature-Policy: geolocation \'none\';');
    }

    public function testFeaturePolicyFromStringParsesDirectivesCorrectly()
    {
        $header = FeaturePolicy::fromString(
            "Feature-Policy: geolocation 'none'; autoplay 'self'; microphone 'self';"
        );
        $this->assertInstanceOf(HeaderInterface::class, $header);
        $this->assertInstanceOf(FeaturePolicy::class, $header);
        $directives = [
            'geolocation' => "'none'",
            'autoplay'  => "'self'",
            'microphone'     => "'self'",
        ];
        $this->assertEquals($directives, $header->getDirectives());
    }

    public function testFeaturePolicyGetFieldNameReturnsHeaderName()
    {
        $header = new FeaturePolicy();
        $this->assertEquals('Feature-Policy', $header->getFieldName());
    }

    public function testFeaturePolicyToStringReturnsHeaderFormattedString()
    {
        $header = FeaturePolicy::fromString(
            "Feature-Policy: geolocation 'none'; autoplay 'self'; microphone 'self';"
        );
        $this->assertInstanceOf(HeaderInterface::class, $header);
        $this->assertInstanceOf(FeaturePolicy::class, $header);
        $this->assertEquals(
            "Feature-Policy: geolocation 'none'; autoplay 'self'; microphone 'self';",
            $header->toString()
        );
    }

    public function testFeaturePolicySetDirective()
    {
        $header = new FeaturePolicy();
        $header->setDirective('geolocation', ['https://*.google.com', 'http://foo.com'])
            ->setDirective('autoplay', ["'self'"])
            ->setDirective('microphone', ['https://*.googleapis.com', 'https://*.bar.com']);
        $header = 'Feature-Policy: geolocation https://*.google.com http://foo.com; '
                . 'autoplay \'self\'; microphone https://*.googleapis.com https://*.bar.com;';
        $this->assertEquals($header, $header->toString());
    }

    public function testFeaturePolicySetDirectiveWithEmptySourcesDefaultsToNone()
    {
        $header = new FeaturePolicy();
        $header->setDirective('geolocation', ["'self'"])
            ->setDirective('autoplay', ['*'])
            ->setDirective('microphone', []);
        $this->assertEquals(
            "Feature-Policy: geolocation 'self'; autoplay *; microphone 'none';",
            $header->toString()
        );
    }

    public function testFeaturePolicySetDirectiveThrowsExceptionIfInvalidDirectiveNameGiven()
    {
        $this->expectException(InvalidArgumentException::class);
        $header = new FeaturePolicy();
        $header->setDirective('foo', []);
    }

    public function testFeaturePolicyGetFieldValueReturnsProperValue()
    {
        $header = new FeaturePolicy();
        $header->setDirective('geolocation', ["'self'"])
            ->setDirective('microphone', ['https://*.github.com']);
        $this->assertEquals("geolocation 'self'; microphone https://*.github.com;", $header->getFieldValue());
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testPreventsCRLFAttackViaFromString()
    {
        $this->expectException(InvalidArgumentException::class);
        FeaturePolicy::fromString("Feature-Policy: default-src 'none'\r\n\r\nevilContent");
    }

    /**
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @group ZF2015-04
     */
    public function testPreventsCRLFAttackViaDirective()
    {
        $header = new FeaturePolicy();
        $this->expectException(InvalidArgumentException::class);
        $header->setDirective('default-src', ["\rsome\r\nCRLF\ninjection"]);
    }
}
