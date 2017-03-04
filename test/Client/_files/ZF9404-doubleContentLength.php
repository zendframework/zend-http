<?php
/**
 * @see       https://github.com/zendframework/zend-http for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-http/blob/master/LICENSE.md New BSD License
 */

$clength = filesize(__FILE__);

header("Content-length: $clength");
header("Content-length: $clength", false);

readfile(__FILE__);
