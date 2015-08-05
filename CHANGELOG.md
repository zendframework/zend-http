# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.5.2 - 2015-08-05

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#7](https://github.com/zendframework/zend-feed/pull/7) fixes a call in the
  proxy adapter to `Response::extractCode()`, which does not exist, to
  `Response::fromString()->getStatusCode()`, which does.
- [#8](https://github.com/zendframework/zend-feed/pull/8) ensures that the Curl
  client adapter enables the `CURLINFO_HEADER_OUT`, which is required to ensure
  we can fetch the raw request after it is sent.
- [#14](https://github.com/zendframework/zend-feed/pull/14) fixes
  `Zend\Http\PhpEnvironment\Request` to ensure that empty `SCRIPT_FILENAME` and
  `SCRIPT_NAME` values which result in an empty `$baseUrl` will not raise an
  `E_WARNING` when used to do a `strpos()` check during base URI detection.
