<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Http\Header;

abstract class AbstractDirectiveBasedHeader implements HeaderInterface
{
    /**
     * Valid directive names
     *
     * @var array
     */
    protected $validDirectiveNames = [];

    /**
     * The directives defined for this policy
     *
     * @var array
     */
    protected $directives = [];

    /**
     * Factory to generate a header object from a string
     *
     * @param string $headerLine
     * @return self
     * @throws Exception\InvalidArgumentException If the header does not match RFC 2616 definition.
     * @see http://tools.ietf.org/html/rfc2616#section-4.2
     */
    public static function fromString($headerLine)
    {
        $header = new static();
        $headerName = $header->getFieldName();
        list($name, $value) = GenericHeader::splitHeaderLine($headerLine);
        // Ensure the proper header name
        if (strcasecmp($name, $headerName) !== 0) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid header line for %s string: "%s"',
                $headerName,
                $name
            ));
        }

        $tokens = explode(';', $value);
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token) {
                list($directiveName, $directiveValue) = explode(' ', $token, 2);
                if (! isset($header->directives[$directiveName])) {
                    $header->setDirective($directiveName, [$directiveValue]);
                }
            }
        }

        return $header;
    }

    /**
     * Get the list of defined directives
     *
     * @return array
     */
    public function getDirectives()
    {
        return $this->directives;
    }

    /**
     * Get the header value
     *
     * @return string
     */
    public function getFieldValue()
    {
        $directives = [];
        foreach ($this->directives as $name => $value) {
            $directives[] = sprintf('%s %s;', $name, $value);
        }
        return implode(' ', $directives);
    }

    /**
     * Return the header as a string
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('%s: %s', $this->getFieldName(), $this->getFieldValue());
    }

    /**
     * Retrieve header name
     *
     * @return string
     */
    abstract public function getFieldName();

    /**
     * Sets the directive to consist of the source list
     *
     * @param string $name The directive name.
     * @param array $sources The source list.
     *
     * @return self
     */
    abstract public function setDirective($name, array $sources);
}
