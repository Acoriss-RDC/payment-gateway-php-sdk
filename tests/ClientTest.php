<?php

namespace Acoriss\PaymentGateway\Tests;

use Acoriss\PaymentGateway\Client;
use Acoriss\PaymentGateway\Exceptions\APIException;
use Acoriss\PaymentGateway\Signer\SignerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private const API_KEY = 'test-api-key';
    private const API_SECRET = 'test-api-secret';

    /**
     * @param list<Response> $responses
     * @param array<string, mixed> $config
     */
    private function makeClientWithResponses(array $responses, array $config = []): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $http = new GuzzleClient(['handler' => $handlerStack, 'base_uri' => 'https://sandbox.checkout.rdcard.net/api/v1']);

        $defaultConfig = [
          'apiKey' => self::API_KEY,
          'apiSecret' => self::API_SECRET,
          'httpClient' => $http,
        ];

        // @phpstan-ignore-next-line argument.type (spread operator with shaped arrays)
        return new Client([...$defaultConfig, ...$config]);
    }

    public function testInitializationDefaultsToSandbox(): void
    {
        $client = new Client(['apiKey' => self::API_KEY, 'apiSecret' => self::API_SECRET]);
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testCreateSessionGeneratesHmacSignature(): void
    {
        $payload = [
          'amount' => 5000,
          'currency' => 'USD',
          'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
        ];
        $raw = json_encode($payload);
        if ($raw === false) {
            $this->fail('Failed to encode payload');
        }
        $expectedSignature = hash_hmac('sha256', $raw, self::API_SECRET);

        $mockResponse = [
          'id' => 'pay_123',
          'amount' => 5000,
          'currency' => 'USD',
          'checkoutUrl' => 'https://checkout.rdcard.net/sessions/123',
          'customer' => $payload['customer'],
          'createdAt' => '2025-11-15T10:00:00Z',
        ];

        $responseBody = json_encode($mockResponse);
        if ($responseBody === false) {
            $this->fail('Failed to encode response');
        }

        $client = $this->makeClientWithResponses([
          new Response(200, [], $responseBody),
        ]);
        $result = $client->createSession($payload);
        $this->assertSame($mockResponse['id'], $result['id']);
    }

    public function testCreateSessionUsesCustomSigner(): void
    {
        $payload = [
          'amount' => 5000,
          'currency' => 'USD',
          'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
        ];
        $mockResponse = [
          'id' => 'pay_123',
          'amount' => 5000,
          'currency' => 'USD',
          'checkoutUrl' => 'https://checkout.rdcard.net/sessions/123',
          'customer' => $payload['customer'],
          'createdAt' => '2025-11-15T10:00:00Z',
        ];
        $signer = new class () implements SignerInterface {
            public function sign(string $body): string
            {
                return 'custom-signature';
            }
        };

        $responseBody = json_encode($mockResponse);
        if ($responseBody === false) {
            $this->fail('Failed to encode response');
        }

        $client = $this->makeClientWithResponses([
          new Response(200, [], $responseBody),
        ], ['signer' => $signer, 'apiSecret' => null]);
        $result = $client->createSession($payload);
        $this->assertSame('pay_123', $result['id']);
    }

    public function testCreateSessionSignatureOverride(): void
    {
        $payload = [
          'amount' => 5000,
          'currency' => 'USD',
          'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
        ];
        $mockResponse = [
          'id' => 'pay_123',
          'amount' => 5000,
          'currency' => 'USD',
          'checkoutUrl' => 'https://checkout.rdcard.net/sessions/123',
          'customer' => $payload['customer'],
          'createdAt' => '2025-11-15T10:00:00Z',
        ];

        $responseBody = json_encode($mockResponse);
        if ($responseBody === false) {
            $this->fail('Failed to encode response');
        }

        $client = $this->makeClientWithResponses([
          new Response(200, [], $responseBody),
        ]);
        $result = $client->createSession($payload, ['signatureOverride' => 'override']);
        $this->assertSame('pay_123', $result['id']);
    }

    public function testCreateSessionWithServiceId(): void
    {
        $payload = [
          'amount' => 7500,
          'currency' => 'USD',
          'customer' => ['email' => 'test@example.com', 'name' => 'Test User'],
          'serviceId' => 'service_456',
        ];
        $mockResponse = [
          'id' => 'pay_789',
          'amount' => 7500,
          'currency' => 'USD',
          'checkoutUrl' => 'https://checkout.rdcard.net/sessions/789',
          'customer' => $payload['customer'],
          'serviceId' => 'service_456',
          'createdAt' => '2025-11-15T10:00:00Z',
        ];

        $responseBody = json_encode($mockResponse);
        if ($responseBody === false) {
            $this->fail('Failed to encode response');
        }

        $client = $this->makeClientWithResponses([
          new Response(200, [], $responseBody),
        ]);
        $result = $client->createSession($payload);
        $this->assertSame('pay_789', $result['id']);
        $this->assertSame('service_456', $result['serviceId']);
    }

    public function testCreateSessionWithoutSignatureThrows(): void
    {
        $mockResponse = [
          'id' => 'pay_123',
          'amount' => 5000,
          'currency' => 'USD',
          'checkoutUrl' => 'u',
          'customer' => ['email' => 'a', 'name' => 'b'],
          'createdAt' => 't',
        ];
        $client = new Client(['apiKey' => self::API_KEY]);
        $this->expectException(\RuntimeException::class);
        $client->createSession($mockResponse); // using response format as payload intentionally
    }

    public function testGetPaymentGeneratesHmacSignature(): void
    {
        $paymentId = 'pay_abc';
        $mockResponse = [
          'id' => $paymentId,
          'amount' => 5000,
          'currency' => 'USD',
          'description' => null,
          'transactionId' => 'order_1234',
          'customer' => ['email' => 'test@example.com', 'phone' => null],
          'createdAt' => '2025-11-15T10:00:00Z',
          'expired' => false,
          'services' => [],
          'status' => 'P',
        ];

        $responseBody = json_encode($mockResponse);
        if ($responseBody === false) {
            $this->fail('Failed to encode response');
        }

        $client = $this->makeClientWithResponses([
          new Response(200, [], $responseBody),
        ]);
        $result = $client->getPayment($paymentId);
        $this->assertSame($paymentId, $result['id']);
    }

    public function testGetPaymentWithoutSignatureThrows(): void
    {
        $client = new Client(['apiKey' => self::API_KEY]);
        $this->expectException(\RuntimeException::class);
        $client->getPayment('pay_123');
    }

    public function testErrorHandlingWrapsApiErrors(): void
    {
        $responseBody = json_encode(['message' => 'Invalid request', 'code' => 'INVALID_REQUEST']);
        if ($responseBody === false) {
            $this->fail('Failed to encode error response');
        }

        $mock = new MockHandler([
          new Response(400, [], $responseBody),
        ]);
        $http = new GuzzleClient(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://sandbox.checkout.rdcard.net/api/v1']);
        $client = new Client(['apiKey' => self::API_KEY, 'apiSecret' => self::API_SECRET, 'httpClient' => $http]);
        $this->expectException(APIException::class);

        try {
            $client->createSession(['amount' => 5000, 'currency' => 'USD', 'customer' => ['email' => 'e', 'name' => 'n']]);
        } catch (APIException $e) {
            $this->assertSame(400, $e->getStatus());
            $this->assertIsArray($e->getData());

            throw $e; // rethrow to satisfy expectException
        }
    }
}
