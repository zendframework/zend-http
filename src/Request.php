<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Http;

use Zend\Http\Header\HeaderInterface;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\ParametersInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Uri\Exception as UriException;
use Zend\Uri\Http as HttpUri;

/**
 * HTTP Request
 *
 * @link      http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5
 */
class Request extends AbstractMessage implements RequestInterface
{
    /**#@+
     * @const string METHOD constant names
     */
    const METHOD_OPTIONS  = 'OPTIONS';
    const METHOD_GET      = 'GET';
    const METHOD_HEAD     = 'HEAD';
    const METHOD_POST     = 'POST';
    const METHOD_PUT      = 'PUT';
    const METHOD_DELETE   = 'DELETE';
    const METHOD_TRACE    = 'TRACE';
    const METHOD_CONNECT  = 'CONNECT';
    const METHOD_PATCH    = 'PATCH';
    const METHOD_PROPFIND = 'PROPFIND';
    /**#@-*/

    /**
     * @var string
     */
    protected $method = self::METHOD_GET;

    /**
     * @var bool
     */
    protected $allowCustomMethods = true;

    /**
     * @var string|HttpUri
     */
    protected $uri;

    /**
     * @var ParametersInterface
     */
    protected $queryParams;

    /**
     * @var ParametersInterface
     */
    protected $postParams;

    /**
     * @var ParametersInterface
     */
    protected $fileParams;

    /**
     * A factory that produces a Request object from a well-formed Http Request string
     *
     * @param  string $string
     * @param  bool $allowCustomMethods
     * @throws Exception\InvalidArgumentException
     * @return static
     */
    public static function fromString($string, $allowCustomMethods = true)
    {
        $request = new static();
        $request->setAllowCustomMethods($allowCustomMethods);

        $lines = explode("\r\n", $string);

        // first line must be Method/Uri/Version string
        $matches   = null;
        $methods   = $allowCustomMethods
            ? '[\w-]+'
            : implode(
                '|',
                [
                    self::METHOD_OPTIONS,
                    self::METHOD_GET,
                    self::METHOD_HEAD,
                    self::METHOD_POST,
                    self::METHOD_PUT,
                    self::METHOD_DELETE,
                    self::METHOD_TRACE,
                    self::METHOD_CONNECT,
                    self::METHOD_PATCH,
                ]
            );

        $regex     = '#^(?P<method>' . $methods . ')\s(?P<uri>[^ ]*)(?:\sHTTP\/(?P<version>\d+\.\d+)){0,1}#';
        /** @var string $firstLine */
        $firstLine = array_shift($lines);
        if (! preg_match($regex, $firstLine, $matches)) {
            throw new Exception\InvalidArgumentException(
                'A valid request line was not found in the provided string'
            );
        }

        $request->setMethod($matches['method']);
        $request->setUri($matches['uri']);

        $parsedUri = parse_url($matches['uri']);

        if (false === $parsedUri) {
            throw new Exception\InvalidArgumentException(
                'A valid request line was not found in the provided string'
            );
        }

        if (array_key_exists('query', $parsedUri)) {
            $parsedQuery = [];
            parse_str($parsedUri['query'], $parsedQuery);
            $request->setQuery(new Parameters($parsedQuery));
        }

        if (isset($matches['version'])) {
            $request->setVersion($matches['version']);
        }

        if (empty($lines)) {
            return $request;
        }

        $isHeader = true;
        $headers = $rawBody = [];
        while ($lines) {
            /** @var string $nextLine */
            $nextLine = array_shift($lines);
            if ($nextLine == '') {
                $isHeader = false;
                continue;
            }

            if ($isHeader) {
                if (preg_match("/[\r\n]/", $nextLine)) {
                    throw new Exception\RuntimeException('CRLF injection detected');
                }
                $headers[] = $nextLine;
                continue;
            }

            if (empty($rawBody)
                && preg_match('/^[a-z0-9!#$%&\'*+.^_`|~-]+:$/i', $nextLine)
            ) {
                throw new Exception\RuntimeException('CRLF injection detected');
            }

            $rawBody[] = $nextLine;
        }

        if ($headers) {
            $request->headers = implode("\r\n", $headers);
        }

        if ($rawBody) {
            $request->setContent(implode("\r\n", $rawBody));
        }

        return $request;
    }

    /**
     * Set the method for this request
     *
     * @param  string $method
     * @return $this
     * @throws Exception\InvalidArgumentException
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);
        if (! defined('static::METHOD_' . $method) && ! $this->getAllowCustomMethods()) {
            throw new Exception\InvalidArgumentException('Invalid HTTP method passed');
        }
        $this->method = $method;
        return $this;
    }

    /**
     * Return the method for this request
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the URI/URL for this request, this can be a string or an instance of Zend\Uri\Http
     *
     * @throws Exception\InvalidArgumentException
     * @param string|HttpUri $uri
     * @return $this
     */
    public function setUri($uri)
    {
        if (is_string($uri)) {
            try {
                $uri = new HttpUri($uri);
            } catch (UriException\InvalidUriPartException $e) {
                throw new Exception\InvalidArgumentException(
                    sprintf('Invalid URI passed as string (%s)', (string) $uri),
                    $e->getCode(),
                    $e
                );
            }
        } elseif (! ($uri instanceof HttpUri)) {
            throw new Exception\InvalidArgumentException(
                'URI must be an instance of Zend\Uri\Http or a string'
            );
        }

        $path = $uri->getPath();

        if (empty($path)) {
            $uri->setPath('/');
        }

        $this->uri = $uri;

        return $this;
    }

    /**
     * Return the URI for this request object
     *
     * @return HttpUri
     */
    public function getUri()
    {
        if ($this->uri === null || is_string($this->uri)) {
            $this->uri = new HttpUri($this->uri);
        }
        return $this->uri;
    }

    /**
     * Return the URI for this request object as a string
     *
     * @return string
     */
    public function getUriString()
    {
        if ($this->uri instanceof HttpUri) {
            return $this->uri->toString();
        }
        return $this->uri;
    }

    /**
     * Provide an alternate Parameter Container implementation for query parameters in this object,
     * (this is NOT the primary API for value setting, for that see getQuery())
     *
     * @param ParametersInterface $query
     * @return $this
     */
    public function setQuery(ParametersInterface $query)
    {
        $this->queryParams = $query;
        return $this;
    }

    /**
     * Return the parameter container responsible for query parameters or a single query parameter
     *
     * @param  string|null $name    Parameter name to retrieve, or null to get the whole container.
     * @param  mixed|null  $default Default value to use when the parameter is missing.
     * @return ParametersInterface|mixed
     */
    public function getQuery($name = null, $default = null)
    {
        if ($this->queryParams === null) {
            $this->queryParams = new Parameters();
        }

        if ($name === null) {
            return $this->queryParams;
        }

        return $this->queryParams->get($name, $default);
    }

    /**
     * Provide an alternate Parameter Container implementation for post parameters in this object,
     * (this is NOT the primary API for value setting, for that see getPost())
     *
     * @param ParametersInterface $post
     * @return $this
     */
    public function setPost(ParametersInterface $post)
    {
        $this->postParams = $post;
        return $this;
    }

    /**
     * Return the parameter container responsible for post parameters or a single post parameter.
     *
     * @param  string|null $name    Parameter name to retrieve, or null to get the whole container.
     * @param  mixed|null  $default Default value to use when the parameter is missing.
     * @return ParametersInterface|mixed
     */
    public function getPost($name = null, $default = null)
    {
        if ($this->postParams === null) {
            $this->postParams = new Parameters();
        }

        if ($name === null) {
            return $this->postParams;
        }

        return $this->postParams->get($name, $default);
    }

    /**
     * Return the Cookie header, this is the same as calling $request->getHeaders()->get('Cookie');
     *
     * @convenience $request->getHeaders()->get('Cookie');
     * @return Header\Cookie|bool
     */
    public function getCookie()
    {
        /** @var Headers $headers */
        $headers = $this->getHeaders();
        /** @var false|Header\Cookie $header */
        $header = $headers->get('Cookie');

        return $header;
    }

    /**
     * Provide an alternate Parameter Container implementation for file parameters in this object,
     * (this is NOT the primary API for value setting, for that see getFiles())
     *
     * @param  ParametersInterface $files
     * @return $this
     */
    public function setFiles(ParametersInterface $files)
    {
        $this->fileParams = $files;
        return $this;
    }

    /**
     * Return the parameter container responsible for file parameters or a single file.
     *
     * @param  string|null $name    Parameter name to retrieve, or null to get the whole container.
     * @param  mixed|null  $default Default value to use when the parameter is missing.
     * @return ParametersInterface|mixed
     */
    public function getFiles($name = null, $default = null)
    {
        if ($this->fileParams === null) {
            $this->fileParams = new Parameters();
        }

        if ($name === null) {
            return $this->fileParams;
        }

        return $this->fileParams->get($name, $default);
    }

    /**
     * Return the header container responsible for headers or all headers of a certain name/type
     *
     * @see \Zend\Http\Headers::get()
     * @param  string|null $name    Header name to retrieve, or null to get the whole container.
     * @param  mixed|null  $default Default value to use when the requested header is missing.
     * @return \Zend\Http\Headers|bool|\Zend\Http\Header\HeaderInterface|\ArrayIterator
     */
    public function getHeaders($name = null, $default = false)
    {
        if ($this->headers === null || is_string($this->headers)) {
            // this is only here for fromString lazy loading
            $this->headers = is_string($this->headers) ? Headers::fromString($this->headers) : new Headers();
        }

        if ($name === null) {
            return $this->headers;
        }

        if ($this->headers->has($name)) {
            return $this->headers->get($name);
        }

        return $default;
    }

    /**
     * Get all headers of a certain name/type.
     *
     * @see Request::getHeaders()
     * @param string|null           $name            Header name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the requested header is missing.
     * @return \Zend\Http\Headers|bool|\Zend\Http\Header\HeaderInterface|\ArrayIterator
     */
    public function getHeader($name, $default = false)
    {
        /** @var false|HeaderInterface $header */
        $header = $this->getHeaders($name, $default);

        return $header;
    }

    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    public function isOptions()
    {
        return ($this->method === self::METHOD_OPTIONS);
    }

    /**
     * Is this a PROPFIND method request?
     *
     * @return bool
     */
    public function isPropFind()
    {
        return ($this->method === self::METHOD_PROPFIND);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    public function isGet()
    {
        return ($this->method === self::METHOD_GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    public function isHead()
    {
        return ($this->method === self::METHOD_HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    public function isPost()
    {
        return ($this->method === self::METHOD_POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    public function isPut()
    {
        return ($this->method === self::METHOD_PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    public function isDelete()
    {
        return ($this->method === self::METHOD_DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    public function isTrace()
    {
        return ($this->method === self::METHOD_TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    public function isConnect()
    {
        return ($this->method === self::METHOD_CONNECT);
    }

    /**
     * Is this a PATCH method request?
     *
     * @return bool
     */
    public function isPatch()
    {
        return ($this->method === self::METHOD_PATCH);
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        /** @var Headers $headers */
        $headers = $this->getHeaders();
        /** @var false|HeaderInterface $header */
        $header = $headers->get('X_REQUESTED_WITH');
        return false !== $header && $header->getFieldValue() === 'XMLHttpRequest';
    }

    /**
     * Is this a Flash request?
     *
     * @return bool
     */
    public function isFlashRequest()
    {
        /** @var Headers $headers */
        $headers = $this->getHeaders();
        /** @var false|HeaderInterface $header */
        $header = $headers->get('USER_AGENT');
        return false !== $header && stristr($header->getFieldValue(), ' flash');
    }

    /**
     * Return the formatted request line (first line) for this http request
     *
     * @return string
     */
    public function renderRequestLine()
    {
        return $this->method . ' ' . (string) $this->uri . ' HTTP/' . $this->version;
    }

    /**
     * @return string
     */
    public function toString()
    {
        /** @var Headers $headers */
        $headers = $this->getHeaders();
        $str = $this->renderRequestLine() . "\r\n";
        $str .= $headers->toString();
        $str .= "\r\n";
        $str .= $this->getContent();
        return $str;
    }

    /**
     * @return bool
     */
    public function getAllowCustomMethods()
    {
        return $this->allowCustomMethods;
    }

    /**
     * @param bool $strictMethods
     */
    public function setAllowCustomMethods($strictMethods)
    {
        $this->allowCustomMethods = (bool) $strictMethods;
    }
}
