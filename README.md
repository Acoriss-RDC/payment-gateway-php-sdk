# Acoriss Payment Gateway PHP SDK

[![CI Status](https://github.com/acoriss/payment-gateway-php-sdk/workflows/CI/badge.svg)](https://github.com/acoriss/payment-gateway-php-sdk/actions)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

A PHP SDK for interacting with the Acoriss Payment Gateway API. Mirrors the functionality of the existing Node.js SDK: creating payment sessions and retrieving payments.

## Installation

Require via Composer (after publishing to Packagist or using VCS repo):

```bash
composer require acoriss/payment-gateway
```

For local development inside this monorepo:

```bash
cd sdks/php
composer install
```

## Quick Start

```php
use Acoriss\PaymentGateway\Client;

$client = new Client([
    'apiKey' => 'your-api-key',
    'apiSecret' => 'your-api-secret', // enables automatic HMAC-SHA256 signing
    // 'environment' => 'live', // default is 'sandbox'
]);

$session = $client->createSession([
    'amount' => 5000,
    'currency' => 'USD',
    'customer' => [
        'email' => 'john@example.com',
        'name' => 'John Doe'
    ],
    'description' => 'Order #1234'
]);

echo $session['checkoutUrl'];

$payment = $client->getPayment($session['id']);
print_r($payment);
```

## Configuration Options

| Key | Type | Description |
| --- | ---- | ----------- |
| apiKey | string | Required API key |
| apiSecret | string | Optional secret for HMAC signing |
| environment | `sandbox|live` | Chooses base URL if `baseUrl` not provided |
| baseUrl | string | Overrides environment base URL |
| signer | `SignerInterface` | Custom signing strategy |
| timeout | float | Timeout in seconds (default 15) |
| logger | `LoggerInterface` | PSR-3 logger for debugging (default: NullLogger) |
| verify | bool\|string | SSL certificate verification (default: true) |

## Features

### Signing

- `createSession` signs the raw JSON request body.
- `getPayment` signs only the payment ID string.
- Provide either `apiSecret`, a custom `SignerInterface`, or pass `signatureOverride` per call.

### Webhook Verification

Verify webhook signatures to ensure authenticity:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

if ($client->verifyWebhookSignature($payload, $signature)) {
    $data = json_decode($payload, true);
    // Process webhook
} else {
    http_response_code(401);
    echo 'Invalid signature';
}
```

### PSR-3 Logging

Add a PSR-3 compatible logger for debugging:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('payment-gateway');
$logger->pushHandler(new StreamHandler('path/to/your.log', Logger::DEBUG));

$client = new Client([
    'apiKey' => 'your-api-key',
    'apiSecret' => 'your-api-secret',
    'logger' => $logger,
]);
```

Logs include:
- Debug: Client initialization, request details
- Info: Successful operations
- Error: Failures and exceptions

## Error Handling

All API/network errors throw `Acoriss\PaymentGateway\Exceptions\APIException` exposing:

```php
try {
    $client->createSession($payload);
} catch (\Acoriss\PaymentGateway\Exceptions\APIException $e) {
    echo $e->getMessage();
    var_dump($e->getStatus(), $e->getData(), $e->getHeaders());
}
```

## Development

### Running Tests

```bash
composer install
composer test
```

### Static Analysis

Run PHPStan for type safety:

```bash
composer analyse
```

### Code Style

Format code with PHP-CS-Fixer:

```bash
# Fix code style
composer format

# Check without fixing
composer format-check
```

## Versioning & Compatibility

SDK targets PHP >= 8.1 and Guzzle 7.x.

## License

MIT
