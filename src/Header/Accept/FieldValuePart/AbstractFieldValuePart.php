<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Http\Header\Accept\FieldValuePart;

/**
 * Field Value Part
 *
 * @see        http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.1
 */
abstract class AbstractFieldValuePart
{
    /**
     * Internal object used for value retrieval
     * @var object
     */
    private $internalValues;

    /**
     * A Field Value Part this Field Value Part matched against.
     * @var AbstractFieldValuePart
     */
    protected $matchedAgainst;

    /**
     * @param object $internalValues
     */
    public function __construct($internalValues)
    {
        $this->internalValues = $internalValues;
    }

    /**
     * Set a Field Value Part this Field Value Part matched against.
     *
     * @param AbstractFieldValuePart $matchedAgainst
     * @return $this
     */
    public function setMatchedAgainst(AbstractFieldValuePart $matchedAgainst)
    {
        $this->matchedAgainst = $matchedAgainst;
        return $this;
    }

    /**
     * Get a Field Value Part this Field Value Part matched against.
     *
     * @return AbstractFieldValuePart|null
     */
    public function getMatchedAgainst()
    {
        return $this->matchedAgainst;
    }

    /**
     * @return object
     */
    protected function getInternalValues()
    {
        return $this->internalValues;
    }

    /**
     * @return string $typeString
     */
    public function getTypeString()
    {
        return $this->typeString;
    }

    /**
     * @return float $priority
     */
    public function getPriority()
    {
        return (float) $this->priority;
    }

    /**
     * @return object $params
     */
    public function getParams()
    {
        return (object) $this->params;
    }

    /**
     * @return string $raw
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getInternalValues()->$key;
    }
}
