# Acoriss Payment Gateway PHP SDK

A PHP SDK for interacting with the Acoriss Payment Gateway API. Mirrors the functionality of the existing Node.js SDK: creating payment sessions and retrieving payments.

## Installation

Require via Composer (after publishing to Packagist or using VCS repo):

```bash
composer require acoriss/payment-gateway-php-sdk
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

## Signing

- `createSession` signs the raw JSON request body.
- `getPayment` signs only the payment ID string.
- Provide either `apiSecret`, a custom `SignerInterface`, or pass `signatureOverride` per call.

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

## Testing

```bash
composer install
composer test
```

## Versioning & Compatibility

SDK targets PHP >= 8.1 and Guzzle 7.x.

## License

MIT
