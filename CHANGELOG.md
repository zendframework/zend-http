# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.5.2 - TBD

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
