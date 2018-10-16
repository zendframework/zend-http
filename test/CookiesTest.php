<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Http;

use PHPUnit\Framework\TestCase;
use Zend\Http\Cookies;
use Zend\Http\Header\SetCookie;
use Zend\Http\Headers;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Response;

class CookiesTest extends TestCase
{
    public function testFromResponseInSetCookie()
    {
        $response = new Response();
        $headers = new Headers();
        $header = new SetCookie('foo', 'bar');
        $header->setDomain('www.zend.com');
        $header->setPath('/');
        $headers->addHeader($header);
        $response->setHeaders($headers);

        $response = Cookies::fromResponse($response, 'http://www.zend.com');
        $this->assertSame($header, $response->getCookie('http://www.zend.com', 'foo'));
    }

    public function testFromResponseInCookie()
    {
        $response = new Response();
        $headers = new Headers();
        $header = new SetCookie('foo', 'bar');
        $header->setDomain('www.zend.com');
        $header->setPath('/');
        $headers->addHeader($header);
        $response->setHeaders($headers);

        $response = Cookies::fromResponse($response, 'http://www.zend.com');
        $this->assertSame($header, $response->getCookie('http://www.zend.com', 'foo'));
    }

    public function testGetAllCookiesStringObject()
    {
        $response = new Response();
        $headers = new Headers();
        $header = new SetCookie('foo', 'bar');
        $header->setDomain('www.zend.com');
        $header->setPath('/');
        $header2 = new SetCookie('foo2', 'bar2');
        $header2->setDomain('www.zend2.com');
        $header2->setPath('/');
        $headers->addHeader($header);
        $headers->addHeader($header2);
        $response->setHeaders($headers);

        $response = Cookies::fromResponse($response, 'http://www.zend.com');
        $result = $response->getAllCookies(Cookies::COOKIE_OBJECT);
        $this->assertSame([$header, $header2], $result);
    }

    public function testGetAllCookiesStringArray()
    {
        $response = new Response();
        $headers = new Headers();
        $header = new SetCookie('foo', 'bar');
        $header->setDomain('www.zend.com');
        $header->setPath('/');
        $header2 = new SetCookie('foo2', 'bar2');
        $header2->setDomain('www.zend2.com');
        $header2->setPath('/');
        $headers->addHeader($header);
        $headers->addHeader($header2);
        $response->setHeaders($headers);

        $expected = [
            'Set-Cookie: foo=bar; Domain=www.zend.com; Path=/',
            'Set-Cookie: foo2=bar2; Domain=www.zend2.com; Path=/',
        ];

        $response = Cookies::fromResponse($response, 'http://www.zend.com');
        $result = $response->getAllCookies(Cookies::COOKIE_STRING_ARRAY);
        $this->assertSame($expected, $result);
    }

    public function testRequestCanHaveArrayCookies()
    {
        $_COOKIE = [
            'test' => [
                'a' => 'value_a',
                'b' => 'value_b',
            ],
        ];
        $request = new Request();
        $fieldValue = $request->getCookie('test')->getFieldValue();
        $this->assertSame('test[a]=value_a; test[b]=value_b', $fieldValue);

        $_COOKIE = [
            'test' => [
                'a' => [
                    'a1' => 'va1',
                    'a2' => 'va2',
                ],
                'b' => [
                    'b1' => 'vb1',
                    'b2' => 'vb2',
                ],
            ],
        ];
        $request = new Request();
        $fieldValue = $request->getCookie('test')->getFieldValue();
        $this->assertSame('test[a][a1]=va1; test[a][a2]=va2; test[b][b1]=vb1; test[b][b2]=vb2', $fieldValue);
    }
}
