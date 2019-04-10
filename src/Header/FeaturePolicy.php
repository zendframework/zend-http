<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Http\Header;

/**
 * Feature Policy (based on Editor’s Draft, 1 April 2019)
 *
 * @link https://w3c.github.io/webappsec-feature-policy/
 */
class FeaturePolicy extends AbstractDirectiveBasedHeader
{
    /**
     * Valid directive names
     *
     * @var array
     *
     * @see https://github.com/w3c/webappsec-feature-policy/blob/master/features.md
     */
    protected $validDirectiveNames = [
        // Standardized Features
        'accelerometer',
        'ambient-light-sensor',
        'autoplay',
        'camera',
        'document-domain',
        'fullscreen',
        'gyroscope',
        'magnetometer',
        'microphone',
        'midi',
        'picture-in-picture',
        'sync-xhr',
        'usb',
        'wake-lock',
        'xr',

        // Proposed Features
        'encrypted-media',
        'geolocation',
        'payment',
        'speaker',

        // Experimental Features
        'document-write',
        'font-display-late-swap',
        'layout-animations',
        'lazyload',
        'legacy-image-formats',
        'oversized-images',
        'sync-script',
        'unoptimized-images',
        'unsized-media',
        'vertical-scroll',
        'serial',
    ];

    /**
     * Sets the directive to consist of the source list
     *
     * @param string $name The directive name.
     * @param array $sources The source list.
     * @return self
     * @throws Exception\InvalidArgumentException If the name is not a valid directive name.
     */
    public function setDirective($name, array $sources)
    {
        if (! in_array($name, $this->validDirectiveNames, true)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a valid directive name; received "%s"',
                __METHOD__,
                (string) $name
            ));
        }

        if (empty($sources)) {
            $this->directives[$name] = "'none'";
            return $this;
        }

        array_walk($sources, [__NAMESPACE__ . '\HeaderValue', 'assertValid']);

        $this->directives[$name] = implode(' ', $sources);

        return $this;
    }

    /**
     * Get the header name
     *
     * @return string
     */
    public function getFieldName()
    {
        return 'Feature-Policy';
    }
}
