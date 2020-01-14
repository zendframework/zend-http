<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Http;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Zend\Http\Client;
use Zend\Http\Client\Adapter\AdapterInterface;
use Zend\Http\Client\Adapter\Curl;
use Zend\Http\Client\Adapter\Proxy;
use Zend\Http\Client\Adapter\Socket;
use Zend\Http\Client\Adapter\Test;
use Zend\Http\Client\Exception as ClientException;
use Zend\Http\Cookies;
use Zend\Http\Exception as HttpException;
use Zend\Http\Header\AcceptEncoding;
use Zend\Http\Header\SetCookie;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Uri\Http;
use Zend\Stdlib;
use ZendTest\Http\TestAsset\ExtendedClient;

class ClientTest extends TestCase
{
    private $originalErrorReporting;
    private $tmpDir;

    protected function setUp()
    {
        $this->originalErrorReporting = \error_reporting();
        $this->tmpDir = \getenv('TMPDIR');

        parent::setUp();
    }

    protected function tearDown()
    {
        \error_reporting($this->originalErrorReporting);
        \putenv('TMPDIR=' . $this->tmpDir);

        parent::tearDown();
    }

    public function testIfCookiesAreSticky()
    {
        $initialCookies = [
            new SetCookie('foo', 'far', null, '/', 'www.domain.com'),
            new SetCookie('bar', 'biz', null, '/', 'www.domain.com'),
        ];

        $requestString = 'GET http://www.domain.com/index.php HTTP/1.1' . "\r\n"
            . 'Host: domain.com' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:16.0) Gecko/20100101 Firefox/16.0' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.5' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Connection: keep-alive' . "\r\n";
        $request = Request::fromString($requestString);

        $client = new Client('http://www.domain.com/');
        $client->setRequest($request);
        $client->addCookie($initialCookies);

        $cookies = new Cookies($client->getRequest()->getHeaders());
        $rawHeaders = 'HTTP/1.1 200 OK' . "\r\n"
            . 'Access-Control-Allow-Origin: *' . "\r\n"
            . 'Content-Encoding: gzip' . "\r\n"
            . 'Content-Type: application/javascript' . "\r\n"
            . 'Date: Sun, 18 Nov 2012 16:16:08 GMT' . "\r\n"
            . 'Server: nginx/1.1.19' . "\r\n"
            . 'Set-Cookie: baz=bah; domain=www.domain.com; path=/' . "\r\n"
            . 'Set-Cookie: joe=test; domain=www.domain.com; path=/' . "\r\n"
            . 'Vary: Accept-Encoding' . "\r\n"
            . 'X-Powered-By: PHP/5.3.10-1ubuntu3.4' . "\r\n"
            . 'Connection: keep-alive' . "\r\n";
        $response = Response::fromString($rawHeaders);
        $client->setResponse($response);

        $cookies->addCookiesFromResponse($client->getResponse(), $client->getUri());

        $client->addCookie($cookies->getMatchingCookies($client->getUri()));

        $this->assertEquals(4, count($client->getCookies()));
    }

    public function testClientRetrievesUppercaseHttpMethodFromRequestObject()
    {
        $client = new Client();
        $client->setMethod('post');
        $this->assertEquals(Client::ENC_URLENCODED, $client->getEncType());
    }

    public function testAcceptEncodingHeaderWorksProperly()
    {
        $method = new ReflectionMethod(Client::class, 'prepareHeaders');
        $method->setAccessible(true);

        $requestString = 'GET http://www.domain.com/index.php HTTP/1.1' . "\r\n"
            . 'Host: domain.com' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:16.0) Gecko/20100101 Firefox/16.0' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.5' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Connection: keep-alive' . "\r\n";
        $request = Request::fromString($requestString);

        $adapter = new Test();

        $client = new Client('http://www.domain.com/');
        $client->setAdapter($adapter);
        $client->setRequest($request);

        $rawHeaders = 'HTTP/1.1 200 OK' . "\r\n"
            . 'Access-Control-Allow-Origin: *' . "\r\n"
            . 'Content-Encoding: gzip, deflate' . "\r\n"
            . 'Content-Type: application/javascript' . "\r\n"
            . 'Date: Sun, 18 Nov 2012 16:16:08 GMT' . "\r\n"
            . 'Server: nginx/1.1.19' . "\r\n"
            . 'Vary: Accept-Encoding' . "\r\n"
            . 'X-Powered-By: PHP/5.3.10-1ubuntu3.4' . "\r\n"
            . 'Connection: keep-alive' . "\r\n";
        $response = Response::fromString($rawHeaders);
        $client->getAdapter()->setResponse($response);

        $headers = $method->invoke($client, $requestString, $client->getUri());
        $this->assertEquals('gzip, deflate', $headers['Accept-Encoding']);
    }

    public function testIfZeroValueCookiesCanBeSet()
    {
        $client = new Client();
        $client->addCookie('test', 0);
        $client->addCookie('test2', '0');
        $client->addCookie('test3', false);

        $this->assertCount(3, $client->getCookies());
    }

    public function testIfNullValueCookiesThrowsException()
    {
        $client = new Client();

        $this->expectException(HttpException\InvalidArgumentException::class);
        $client->addCookie('test', null);
    }

    public function testIfCookieHeaderCanBeSet()
    {
        $header = [new SetCookie('foo', 'bar')];
        $client = new Client();
        $client->addCookie($header);

        $cookies = $client->getCookies();
        $this->assertEquals(1, count($cookies));
        $this->assertEquals($header[0], $cookies['foo']);
    }

    public function testIfArrayOfHeadersCanBeSet()
    {
        $headers = [
            new SetCookie('foo'),
            new SetCookie('bar'),
        ];

        $client = new Client();
        $client->addCookie($headers);

        $cookies = $client->getCookies();
        $this->assertEquals(2, count($cookies));
    }

    public function testIfArrayIteratorOfHeadersCanBeSet()
    {
        $headers = new ArrayIterator([
            new SetCookie('foo'),
            new SetCookie('bar'),
        ]);

        $client = new Client();
        $client->addCookie($headers);

        $cookies = $client->getCookies();
        $this->assertEquals(2, count($cookies));
    }

    /**
     * @group 2774
     * @group 2745
     */
    public function testArgSeparatorDefaultsToIniSetting()
    {
        $argSeparator = ini_get('arg_separator.output');
        $client = new Client();
        $this->assertEquals($argSeparator, $client->getArgSeparator());
    }

    /**
     * @group 2774
     * @group 2745
     */
    public function testArgSeparatorDefaultsWithNoIniSetting()
    {
        \ini_set('arg_separator.output', false);
        $client = new Client();
        $this->assertEquals('&', $client->getArgSeparator());
    }

    /**
     * @group 2774
     * @group 2745
     */
    public function testCanOverrideArgSeparator()
    {
        $client = new Client();
        $client->setArgSeparator(';');
        $this->assertEquals(';', $client->getArgSeparator());
    }

    public function testClientUsesAcceptEncodingHeaderFromRequestObject()
    {
        $client = new Client('http://foo.com');

        $client->setAdapter(Test::class);

        $request = $client->getRequest();

        $acceptEncodingHeader = new AcceptEncoding();
        $acceptEncodingHeader->addEncoding('foo', 1);
        $request->getHeaders()->addHeader($acceptEncodingHeader);

        $client->send();

        $rawRequest = $client->getLastRawRequest();

        $this->assertNotContains('Accept-Encoding: gzip, deflate', $rawRequest, '', true);
        $this->assertNotContains('Accept-Encoding: identity', $rawRequest, '', true);

        $this->assertContains('Accept-Encoding: foo', $rawRequest);
    }

    public function testEncodeAuthHeaderWorksAsExpected()
    {
        $encoded = Client::encodeAuthHeader('test', 'test');
        $this->assertEquals('Basic ' . base64_encode('test:test'), $encoded);
    }

    public function testEncodeAuthHeaderThrowsExceptionWhenUsernameContainsSemiColon()
    {
        $this->expectException(ClientException\InvalidArgumentException::class);
        Client::encodeAuthHeader('test:', 'test');
    }

    public function testEncodeAuthHeaderThrowsExceptionWhenInvalidAuthTypeIsUsed()
    {
        $this->expectException(ClientException\InvalidArgumentException::class);
        Client::encodeAuthHeader('test', 'test', 'test');
    }

    public function testIfMaxredirectWorksCorrectly()
    {
        $testAdapter = new Test();
        // first response, contains a redirect
        $testAdapter->setResponse(
            'HTTP/1.1 303 See Other' . "\r\n"
            . 'Location: http://www.example.org/part2' . "\r\n\r\n"
            . 'Page #1'
        );
        // seconds response, contains a redirect
        $testAdapter->addResponse(
            'HTTP/1.1 303 See Other' . "\r\n"
            . 'Location: http://www.example.org/part3' . "\r\n\r\n"
            . 'Page #2'
        );
        // third response
        $testAdapter->addResponse(
            'HTTP/1.1 303 See Other' . "\r\n\r\n"
            . 'Page #3'
        );

        // create a client which allows one redirect at most!
        $client = new Client('http://www.example.org/part1', [
            'adapter' => $testAdapter,
            'maxredirects' => 1,
            'storeresponse' => true,
        ]);

        // do the request
        $response = $client->setMethod('GET')->send();

        // response should be the second response, since third response should not
        // be requested, due to the maxredirects = 1 limit
        $this->assertEquals($response->getContent(), 'Page #2');
    }

    public function testSendWithNotUriPath()
    {
        $testAdapter = new Test();
        $testAdapter->setResponse(
            'HTTP/1.1 200 OK' . "\r\n\r\n"
            . 'Page #1'
        );

        $uri = new Http();
        $uri->setHost('www.example.org');

        $this->assertNull($uri->getPath());

        $client = new Client($uri, [
            'adapter' => $testAdapter,
            'storeresponse' => true,
        ]);

        // do the request
        $response = $client->setMethod('GET')->send();

        $this->assertEquals($response->getContent(), 'Page #1');
    }

    public function testHappyPathWithDispatch()
    {
        $testAdapter = new Test();
        $testAdapter->setResponse(
            'HTTP/1.1 200 OK' . "\r\n\r\n"
            . 'Page #1'
        );

        $client = new Client('http://www.example.org/part1', [
            'adapter' => $testAdapter,
            'storeresponse' => true,
        ]);

        $request = new Request();
        $request->setUri('http://www.example.org/part1');
        $response = new Response();

        // do the request
        $response = $client->setMethod('GET')->dispatch($request, $response);

        $this->assertEquals($response->getContent(), 'Page #1');
    }

    public function testDispatchWithBaseInterface()
    {
        $this->expectException(HttpException\UnexpectedValueException::class);

        $testAdapter = new Test();
        $testAdapter->setResponse(
            'HTTP/1.1 200 OK' . "\r\n\r\n"
            . 'Page #1'
        );

        $client = new Client('http://www.example.org/part1', [
            'adapter' => $testAdapter,
            'storeresponse' => true,
        ]);

        $request = $this->prophesize(Stdlib\RequestInterface::class);
        $response = new Response();

        // do the request
        $client->setMethod('GET')->dispatch($request->reveal(), $response);
    }

    public function testIfClientDoesNotLooseAuthenticationOnRedirect()
    {
        // set up user credentials
        $user = 'username123';
        $password = 'password456';
        $encoded = Client::encodeAuthHeader($user, $password, Client::AUTH_BASIC);

        // set up two responses that simulate a redirection
        $testAdapter = new Test();
        $testAdapter->setResponse(
            'HTTP/1.1 303 See Other' . "\r\n"
            . 'Location: http://www.example.org/part2' . "\r\n\r\n"
            . 'The URL of this page has changed.'
        );
        $testAdapter->addResponse(
            'HTTP/1.1 200 OK' . "\r\n\r\n"
            . 'Welcome to this Website.'
        );

        // create client with HTTP basic authentication
        $client = new Client('http://www.example.org/part1', [
            'adapter' => $testAdapter,
            'maxredirects' => 1,
        ]);
        $client->setAuth($user, $password, Client::AUTH_BASIC);

        // do request
        $client->setMethod('GET')->send();

        // the last request should contain the Authorization header
        $this->assertContains($encoded, $client->getLastRawRequest());
    }

    public function testIfClientDoesNotForwardAuthenticationToForeignHost()
    {
        // set up user credentials
        $user = 'username123';
        $password = 'password456';
        $encoded = Client::encodeAuthHeader($user, $password, Client::AUTH_BASIC);

        $testAdapter = new Test();
        $client = new Client(null, ['adapter' => $testAdapter]);

        // set up two responses that simulate a redirection from example.org to example.com
        $testAdapter->setResponse(
            'HTTP/1.1 303 See Other' . "\r\n"
            . 'Location: http://example.com/part2' . "\r\n\r\n"
            . 'The URL of this page has changed.'
        );
        $testAdapter->addResponse(
            'HTTP/1.1 200 OK' . "\r\n\r\n"
            . 'Welcome to this Website.'
        );

        // set auth and do request
        $client->setUri('http://example.org/part1')
            ->setAuth($user, $password, Client::AUTH_BASIC);
        $client->setMethod('GET')->send();

        // the last request should NOT contain the Authorization header,
        // because example.com is different from example.org
        $this->assertNotContains($encoded, $client->getLastRawRequest());

        // set up two responses that simulate a redirection from example.org to sub.example.org
        $testAdapter->setResponse(
            'HTTP/1.1 303 See Other' . "\r\n"
            . 'Location: http://sub.example.org/part2' . "\r\n\r\n"
            . 'The URL of this page has changed.'
        );
        $testAdapter->addResponse(
            'HTTP/1.1 200 OK' . "\r\n\r\n"
            . 'Welcome to this Website.'
        );

        // set auth and do request
        $client->setUri('http://example.org/part1')
            ->setAuth($user, $password, Client::AUTH_BASIC);
        $client->setMethod('GET')->send();

        // the last request should contain the Authorization header,
        // because sub.example.org is a subdomain unter example.org
        $this->assertContains($encoded, $client->getLastRawRequest());

        // set up two responses that simulate a rediration from sub.example.org to example.org
        $testAdapter->setResponse(
            'HTTP/1.1 303 See Other' . "\r\n"
            . 'Location: http://example.org/part2' . "\r\n\r\n"
            . 'The URL of this page has changed.'
        );
        $testAdapter->addResponse(
            'HTTP/1.1 200 OK' . "\r\n\r\n"
            . 'Welcome to this Website.'
        );

        // set auth and do request
        $client->setUri('http://sub.example.org/part1')
            ->setAuth($user, $password, Client::AUTH_BASIC);
        $client->setMethod('GET')->send();

        // the last request should NOT contain the Authorization header,
        // because example.org is not a subdomain unter sub.example.org
        $this->assertNotContains($encoded, $client->getLastRawRequest());
    }

    public function testAdapterAlwaysReachableIfSpecified()
    {
        $testAdapter = new Test();
        $client = new Client('http://www.example.org/', [
            'adapter' => $testAdapter,
        ]);

        $this->assertSame($testAdapter, $client->getAdapter());
    }

    public function testPrepareHeadersCreateRightHttpField()
    {
        $body = json_encode(['foofoo' => 'barbar']);

        $client = new Client();
        $prepareHeadersReflection = new ReflectionMethod($client, 'prepareHeaders');
        $prepareHeadersReflection->setAccessible(true);

        $request = new Request();
        $request->getHeaders()->addHeaderLine('content-type', 'application/json');
        $request->getHeaders()->addHeaderLine('content-length', strlen($body));
        $client->setRequest($request);

        $client->setEncType('application/json');

        $this->assertSame($client->getRequest(), $request);

        $headers = $prepareHeadersReflection->invoke($client, $body, new Http('http://localhost:5984'));

        $this->assertArrayNotHasKey('content-type', $headers);
        $this->assertArrayHasKey('Content-Type', $headers);

        $this->assertArrayNotHasKey('content-length', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
    }

    public function testPrepareHeadersCurlDigestAuthentication()
    {
        $body = json_encode(['foofoo' => 'barbar']);

        $client = new Client();
        $prepareHeadersReflection = new ReflectionMethod($client, 'prepareHeaders');
        $prepareHeadersReflection->setAccessible(true);

        $request = new Request();
        $request->getHeaders()->addHeaderLine('Authorization: Digest');
        $request->getHeaders()->addHeaderLine('content-type', 'application/json');
        $request->getHeaders()->addHeaderLine('content-length', strlen($body));
        $client->setRequest($request);

        $this->assertSame($client->getRequest(), $request);

        $headers = $prepareHeadersReflection->invoke($client, $body, new Http('http://localhost:5984'));

        $this->assertInternalType('array', $headers);
        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertContains('Digest', $headers['Authorization']);
    }

    /**
     * @group 6301
     */
    public function testCanSpecifyCustomAuthMethodsInExtendingClasses()
    {
        $client = new ExtendedClient();

        $client->setAuth('username', 'password', ExtendedClient::AUTH_CUSTOM);

        $this->assertAttributeEquals(
            [
                'user'     => 'username',
                'password' => 'password',
                'type'     => ExtendedClient::AUTH_CUSTOM,
            ],
            'auth',
            $client
        );
    }

    /**
     * @group 6231
     */
    public function testHttpQueryParametersCastToString()
    {
        $client = new Client();

        $adapter = $this->createMock(AdapterInterface::class);

        $client->setAdapter($adapter);

        $request = new Request();

        $request->setUri('http://example.com/');
        $request->getQuery()->set('foo', 'bar');

        $response = new Response();

        $adapter
            ->expects($this->once())
            ->method('write')
            ->with(Request::METHOD_GET, 'http://example.com/?foo=bar');

        $adapter
            ->expects($this->any())
            ->method('read')
            ->will($this->returnValue($response->toString()));

        $client->send($request);
    }

    /**
     * @group 6959
     */
    public function testClientRequestMethod()
    {
        $request = new Request();
        $request->setUri('http://foo.com');
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->set('data', 'random');

        $client = new Client();
        $client->setAdapter(Test::class);
        $client->send($request);

        $this->assertSame(Client::ENC_URLENCODED, $client->getEncType());
    }

    /**
     * @group 7332
     */
    public function testAllowsClearingEncType()
    {
        $client = new Client();
        $client->setEncType('application/x-www-form-urlencoded');

        $this->assertEquals('application/x-www-form-urlencoded', $client->getEncType());

        $client->setEncType(null);
        $this->assertSame('', $client->getEncType());
    }

    /**
     * @see https://github.com/zendframework/zend-http/issues/33
     */
    public function testFormUrlEncodeSeparator()
    {
        $client = new Client();
        $client->setEncType('application/x-www-form-urlencoded');
        $request = new Request();
        $request->setUri('http://foo.com');
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->set('foo', 'bar');
        $request->getPost()->set('baz', 'foo');
        ini_set('arg_separator.output', '$');
        $client->setAdapter(Test::class);
        $client->send($request);
        $rawRequest = $client->getLastRawRequest();
        $this->assertContains('foo=bar&baz=foo', $rawRequest);
    }

    public function testRelativeUriInConstructorIsNotAllowed()
    {
        $this->expectException(HttpException\InvalidArgumentException::class);
        $client = new Client('/example');
    }

    public function testRelativeUriIsNotAllowed()
    {
        $this->expectException(HttpException\InvalidArgumentException::class);
        $client = new Client('http://www.domain.com');
        $client->setUri('/example');
    }

    public function testRelativeUriIsNotAllowedSendingRequest()
    {
        $this->expectException(HttpException\InvalidArgumentException::class);

        $client = new Client();
        $uri = new Http();
        $request = new Request();
        $request->setUri($uri);
        $request->setMethod(Request::METHOD_GET);
        $client->setAdapter(Test::class);
        $client->send($request);
    }

    public function portChangeDataProvider()
    {
        return [
            'default-https' => ['https://localhost/example', 443],
            'default-http' => ['http://localhost/example', 80]
        ];
    }

    /**
     * @dataProvider portChangeDataProvider
     */
    public function testUriPortIsSetToAppropriateDefaultValueWhenAnUriOmittingThePortIsProvided($absoluteURI, $port)
    {
        $client = new Client();
        $client->getUri()->setPort(null);

        $client->setUri($absoluteURI);
        $this->assertSame($port, $client->getUri()->getPort());

        $client->setAdapter(Test::class);
        $client->send();
        $this->assertSame($port, $client->getUri()->getPort());
    }

    public function testUriPortIsNotSetWhenUriIsRelative()
    {
        $client = new Client('/example');
        $this->assertNull($client->getUri()->getPort());

        $client->setAdapter(Test::class);
        $client->send();
        $this->assertNull($client->getUri()->getPort());
    }

    public function cookies()
    {
        yield 'name-value' => [['cookie-name' => 'cookie-value']];
        yield 'SetCookie' => [[new SetCookie('cookie-name', 'cookie-value')]];
    }

    /**
     * @dataProvider cookies
     */
    public function testSetCookies(array $cookies)
    {
        $client = new Client();

        $client->setCookies($cookies);

        self::assertCount(1, $client->getCookies());
        self::assertContainsOnlyInstancesOf(SetCookie::class, $client->getCookies());
    }

    public function testSetCookieAcceptOnlyArray()
    {
        $client = new Client();

        $this->expectException(HttpException\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cookies passed as parameter, it must be an array');
        $client->setCookies(new SetCookie('name', 'value'));
    }

    /**
     * @return AdapterInterface[]
     */
    public function adapterWithStreamSupport()
    {
        yield 'curl' => [new Curl()];
        yield 'proxy' => [new Proxy()];
        yield 'socket' => [new Socket()];
    }

    /**
     * @dataProvider adapterWithStreamSupport
     */
    public function testStreamCompression(AdapterInterface $adapter)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'stream');

        $client = new Client('https://www.gnu.org/licenses/gpl-3.0.txt');
        $client->setAdapter($adapter);
        $client->setStream($tmpFile);
        $client->send();

        $response = $client->getResponse();

        self::assertSame($response->getBody(), file_get_contents($tmpFile));
    }

    public function testClientRequestWillNotThrowExceptionOnResponseWithNoCookies()
    {
        $request = new Request();
        $request->setUri('http://www.domain.com');
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->set('data', 'random');

        $response = new Response();

        $adapter = new Test();

        $adapter->setResponse([
            0 => $response,
        ]);

        $client = new Client();
        $client->setAdapter($adapter);
        $client->send($request);

        $this->assertCount(0, $client->getCookies());
    }

    public function testClientRequestWillSaveCookiesAndReuseThem()
    {
        $cookies = new Cookies();
        $cookies->addCookie(new SetCookie('foo', 'far', null, '/', 'www.domain.com'));
        $cookies->addCookie(new SetCookie('bar', 'far', null, '/', 'www.domain.com'));

        $request = new Request();
        $request->setUri('http://www.domain.com');
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->set('data', 'random');

        $response = new Response();
        $response->getHeaders()->addHeaders($cookies->getAllCookies());

        $adapter = new Test();

        $adapter->setResponse([
            0 => $response,
        ]);

        $client = new Client();
        $client->setAdapter($adapter);
        $client->send($request);

        $this->assertCount(2, $client->getCookies());

        $request2 = new Request();
        $request2->setUri('http://www.domain.com');

        $client->send($request2);

        $lastRawRequest = $client->getLastRawRequest();
        $this->assertContains("\r\nCookie: foo=far; bar=far\r\n", $lastRawRequest);
    }

    public function testClientRequestWillSaveCookiesAndReuseThemWithNullPathUri()
    {
        $cookies = new Cookies();
        $cookies->addCookie(new SetCookie('foo', 'far', null, '/', 'www.domain.com'));
        //$cookies->addCookie(new SetCookie('foo2', 'far', null, '/foo', 'www.domain.com'));
        $cookies->addCookie(new SetCookie('bar', 'far', null, '/', 'www.domain.com'));

        $uri = new Http();
        $uri->setHost('www.domain.com');

        $this->assertNull($uri->getPath());

        $request = new Request();
        $request->setUri('http://www.domain.com');
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->set('data', 'random');

        $response = new Response();
        $response->getHeaders()->addHeaders($cookies->getAllCookies());

        $adapter = new Test();

        $adapter->setResponse([
            0 => $response,
        ]);

        $client = new Client();
        $client->setAdapter($adapter);
        $client->send($request);

        $this->assertCount(2, $client->getCookies());

        $request2 = new Request();
        $request2->setUri($uri);

        $client->send($request2);

        $lastRawRequest = $client->getLastRawRequest();
        $this->assertContains("\r\nCookie: foo=far; bar=far\r\n", $lastRawRequest);
    }

    public function testClientRequestWillNotReuseCookiesFromDifferentDomain()
    {
        $cookies = new Cookies();
        $cookies->addCookie(new SetCookie('foo', 'far', null, '/', 'www.domain.com'));
        $cookies->addCookie(new SetCookie('bar', 'far', null, '/', 'www.domain.com'));

        $request = new Request();
        $request->setUri('http://www.domain.com');
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->set('data', 'random');

        $response = new Response();
        $response->getHeaders()->addHeaders($cookies->getAllCookies());

        $adapter = new Test();

        $adapter->setResponse([
            0 => $response,
        ]);

        $client = new Client();
        $client->setAdapter($adapter);
        $client->send($request);

        $this->assertCount(2, $client->getCookies());

        $request2 = new Request();
        $request2->setUri('http://foo.com');

        $client->send($request2);

        $lastRawRequest = $client->getLastRawRequest();
        $this->assertNotContains("\r\nCookie: foo=far; bar=far\r\n", $lastRawRequest);
    }

    public function testDoRequestWithNoHostUri()
    {
        $this->expectException(HttpException\InvalidArgumentException::class);

        $client = new Client();
        $class = new ReflectionClass(Client::class);
        $method = $class->getMethod('doRequest');
        $method->setAccessible(true);

        $uri = new Http();

        $method->invokeArgs($client, [$uri, 'GET']);
    }
}
