<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Http;

use PHPUnit\Framework\TestCase;
use Zend\Http\Exception\InvalidArgumentException;
use Zend\Http\Header;

class HeaderTest extends TestCase
{
    public function header()
    {
        // @codingStandardsIgnoreStart
        yield Header\AcceptRanges::class            => [Header\AcceptRanges::class, 'Accept-Ranges'];
        yield Header\AuthenticationInfo::class      => [Header\AuthenticationInfo::class, 'Authentication-Info'];
        yield Header\Authorization::class           => [Header\Authorization::class, 'Authorization'];
        yield Header\ContentDisposition::class      => [Header\ContentDisposition::class, 'Content-Disposition'];
        yield Header\ContentEncoding::class         => [Header\ContentEncoding::class, 'Content-Encoding'];
        yield Header\ContentLanguage::class         => [Header\ContentLanguage::class, 'Content-Language'];
        yield Header\ContentLength::class           => [Header\ContentLength::class, 'Content-Length'];
        yield Header\ContentMD5::class              => [Header\ContentMD5::class, 'Content-MD5'];
        yield Header\ContentRange::class            => [Header\ContentRange::class, 'Content-Range'];
        yield Header\ContentTransferEncoding::class => [Header\ContentTransferEncoding::class, 'Content-Transfer-Encoding'];
        yield Header\ContentType::class             => [Header\ContentType::class, 'Content-Type'];
        yield Header\Etag::class                    => [Header\Etag::class, 'Etag'];
        yield Header\Expect::class                  => [Header\Expect::class, 'Expect'];
        yield Header\From::class                    => [Header\From::class, 'From'];
        yield Header\Host::class                    => [Header\Host::class, 'Host'];
        yield Header\IfMatch::class                 => [Header\IfMatch::class, 'If-Match'];
        yield Header\IfNoneMatch::class             => [Header\IfNoneMatch::class, 'If-None-Match'];
        yield Header\IfRange::class                 => [Header\IfRange::class, 'If-Range'];
        yield Header\KeepAlive::class               => [Header\KeepAlive::class, 'Keep-Alive'];
        yield Header\MaxForwards::class             => [Header\MaxForwards::class, 'Max-Forwards'];
        yield Header\Origin::class                  => [Header\Origin::class, 'Origin'];
        yield Header\Pragma::class                  => [Header\Pragma::class, 'Pragma'];
        yield Header\ProxyAuthenticate::class       => [Header\ProxyAuthenticate::class, 'Proxy-Authenticate'];
        yield Header\ProxyAuthorization::class      => [Header\ProxyAuthorization::class, 'Proxy-Authorization'];
        yield Header\Range::class                   => [Header\Range::class, 'Range'];
        yield Header\Refresh::class                 => [Header\Refresh::class, 'Refresh'];
        yield Header\Server::class                  => [Header\Server::class, 'Server'];
        yield Header\TE::class                      => [Header\TE::class, 'TE'];
        yield Header\Trailer::class                 => [Header\Trailer::class, 'Trailer'];
        yield Header\TransferEncoding::class        => [Header\TransferEncoding::class, 'Transfer-Encoding'];
        yield Header\Upgrade::class                 => [Header\Upgrade::class, 'Upgrade'];
        yield Header\UserAgent::class               => [Header\UserAgent::class, 'User-Agent'];
        yield Header\Vary::class                    => [Header\Vary::class, 'Vary'];
        yield Header\Via::class                     => [Header\Via::class, 'Via'];
        yield Header\Warning::class                 => [Header\Warning::class, 'Warning'];
        yield Header\WWWAuthenticate::class         => [Header\WWWAuthenticate::class, 'WWW-Authenticate'];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider header
     *
     * @param string $class
     * @param string $name
     */
    public function testThrowsExceptionIfInvalidHeaderLine($class, $name)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header line for ' . $name . ' string');
        $class::fromString($name . '-Foo: bar');
    }

    /**
     * @dataProvider header
     *
     * @param string $class
     * @param string $name
     */
    public function testCaseInsensitiveHeaderName($class, $name)
    {
        $header1 = $class::fromString(strtoupper($name) . ': foo');
        self::assertSame('foo', $header1->getFieldValue());

        $header2 = $class::fromString(strtolower($name) . ': bar');
        self::assertSame('bar', $header2->getFieldValue());
    }

    /**
     * @dataProvider header
     *
     * @param string $class
     * @param string $name
     */
    public function testDefaultValues($class, $name)
    {
        $header = new $class();

        self::assertSame('', $header->getFieldValue());
        self::assertSame($name, $header->getFieldName());
        self::assertSame($name . ': ', $header->toString());
    }

    /**
     * @dataProvider header
     *
     * @param string $class
     * @param string $name
     */
    public function testSetValueViaConstructor($class, $name)
    {
        $header = new $class('foo-bar');

        self::assertSame('foo-bar', $header->getFieldValue());
        self::assertSame($name . ': foo-bar', $header->toString());
    }

    /**
     * @dataProvider header
     *
     * @param string $class
     * @param string $name
     *
     * Note: in theory this is invalid, as we would expect value to be string|null.
     * Null is default value but it is converted to string.
     */
    public function testSetIntValueViaConstructor($class, $name)
    {
        $header = new $class(100);

        self::assertSame('100', $header->getFieldValue());
        self::assertSame($name . ': 100', $header->toString());
    }

    /**
     * @dataProvider header
     *
     * @param string $class
     * @param string $name
     */
    public function testSetZeroStringValueViaConstructor($class, $name)
    {
        $header = new $class('0');

        self::assertSame('0', $header->getFieldValue());
        self::assertSame($name . ': 0', $header->toString());
    }

    /**
     * @dataProvider header
     *
     * @param string $class
     * @param string $name
     */
    public function testFromStringWithNumber($class, $name)
    {
        $header = $class::fromString($name . ': 100');

        self::assertSame('100', $header->getFieldValue());
        self::assertSame($name . ': 100', $header->toString());
    }
}
