# Changelog

All notable changes to the Acoriss Payment Gateway PHP SDK will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- PSR-3 logging support via optional `logger` configuration parameter
- `verifyWebhookSignature()` method for webhook signature validation
- Comprehensive PHPDoc annotations with `@throws` declarations
- Type hints using `Types::` references for better IDE autocomplete
- Support for `verify` SSL configuration option in HTTP client
- PHPStan static analysis at level 8
- PHP-CS-Fixer for code style consistency
- GitHub Actions CI/CD pipeline
- Composer scripts: `analyse`, `format`, `format-check`

### Changed
- Enhanced error messages with contextual logging
- Return type `void` changed to `never` for `rethrowApiException()` method
- Improved `Types.php` documentation with Config type shape
- `composer.lock` is now committed for reproducible development environments

### Fixed
- Type safety improvements throughout codebase

## [1.0.0] - 2025-11-19

### Added
- Initial release of Acoriss Payment Gateway PHP SDK
- Payment session creation via `createSession()`
- Payment retrieval via `getPayment()`
- HMAC-SHA256 request signing
- Custom signer interface support
- Comprehensive error handling with `APIException`
- PHPUnit test suite
- Support for sandbox and live environments
