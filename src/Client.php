<?php

namespace Acoriss\PaymentGateway;

use Acoriss\PaymentGateway\Exceptions\APIException;
use Acoriss\PaymentGateway\Signer\HmacSha256Signer;
use Acoriss\PaymentGateway\Signer\SignerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Payment Gateway Client
 *
 * createSession signs the raw JSON body.
 * getPayment signs the payment ID string.
 */
class Client
{
    private const BASE_URLS = [
      'sandbox' => 'https://sandbox.checkout.rdcard.net/api/v1',
      'live' => 'https://checkout.rdcard.net/api/v1',
    ];

    private readonly string $apiKey;
    private readonly ?SignerInterface $signer;
    private readonly GuzzleClient $http;
    private readonly LoggerInterface $logger;

    /**
     * @param array{apiKey:string,apiSecret?:string,environment?:string,baseUrl?:string,signer?:SignerInterface,timeout?:float,httpClient?:GuzzleClient,logger?:LoggerInterface,retries?:int,verify?:bool|string} $config
     * @throws \InvalidArgumentException if apiKey is missing
     */
    public function __construct(array $config)
    {
        $environment = $config['environment'] ?? 'sandbox';
        $baseUrl = $config['baseUrl'] ?? self::BASE_URLS[$environment] ?? self::BASE_URLS['sandbox'];
        $this->apiKey = $config['apiKey'];
        $this->signer = $config['signer'] ?? (isset($config['apiSecret']) ? new HmacSha256Signer($config['apiSecret']) : null);
        $this->logger = $config['logger'] ?? new NullLogger();

        $httpOptions = [
          'base_uri' => $baseUrl,
          'timeout' => $config['timeout'] ?? 15.0,
          'headers' => [
            'Content-Type' => 'application/json',
          ],
        ];

        if (isset($config['verify'])) {
            $httpOptions['verify'] = $config['verify'];
        }

        $this->http = $config['httpClient'] ?? new GuzzleClient($httpOptions);

        $this->logger->debug('PaymentGateway Client initialized', [
          'environment' => $environment,
          'baseUrl' => $baseUrl,
        ]);
    }

    /**
     * Create a new payment session
     *
     * @param array<string, mixed> $payload Payment session request shape (see README or Types::PaymentSessionRequest)
     * @param array{signatureOverride?:string} $opts
     * @return array<string, mixed> Decoded JSON payment session response (Types::PaymentSessionResponse)
     * @throws \InvalidArgumentException if payload cannot be JSON encoded
     * @throws \RuntimeException if no signature method is available
     * @throws APIException on API errors or network failures
     */
    public function createSession(array $payload, array $opts = []): array
    {
        $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($rawBody === false) {
            $this->logger->error('Failed to encode payload to JSON', ['payload' => $payload]);

            throw new \InvalidArgumentException('Failed to encode payload to JSON');
        }
        $signature = $opts['signatureOverride'] ?? ($this->signer?->sign($rawBody));
        if (!$signature) {
            $this->logger->error('No signature available for createSession');

            throw new \RuntimeException('No signature available. Provide apiSecret at client init, a custom signer, or pass signatureOverride.');
        }

        $this->logger->debug('Creating payment session', ['amount' => $payload['amount'] ?? null]);

        try {
            $response = $this->http->post('/sessions', [
              'body' => $rawBody,
              'headers' => [
                'X-API-KEY' => $this->apiKey,
                'X-SIGNATURE' => $signature,
              ],
            ]);
            $result = $this->decodeJson($response);
            $this->logger->info('Payment session created', ['sessionId' => $result['id'] ?? null]);

            return $result;
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to create payment session', ['exception' => $e->getMessage()]);
            $this->rethrowApiException($e);
        }
    }

    /**
     * Retrieve a payment session by ID
     *
     * @param string $paymentId The payment session ID
     * @param array{signatureOverride?:string} $opts
     * @return array<string, mixed> Decoded JSON retrieve payment response (Types::RetrievePaymentResponse)
     * @throws \RuntimeException if no signature method is available
     * @throws APIException on API errors or network failures
     */
    public function getPayment(string $paymentId, array $opts = []): array
    {
        $signature = $opts['signatureOverride'] ?? ($this->signer?->sign($paymentId));
        if (!$signature) {
            $this->logger->error('No signature available for getPayment');

            throw new \RuntimeException('No signature available. Provide apiSecret at client init, a custom signer, or pass signatureOverride.');
        }

        $this->logger->debug('Retrieving payment', ['paymentId' => $paymentId]);

        try {
            $response = $this->http->get('sessions/' . rawurlencode($paymentId), [
              'headers' => [
                'X-API-KEY' => $this->apiKey,
                'X-SIGNATURE' => $signature,
              ],
            ]);
            $result = $this->decodeJson($response);
            $this->logger->info('Payment retrieved', ['paymentId' => $paymentId, 'status' => $result['status'] ?? null]);

            return $result;
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to retrieve payment', ['paymentId' => $paymentId, 'exception' => $e->getMessage()]);
            $this->rethrowApiException($e);
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload The raw webhook payload
     * @param string $signature The signature from webhook headers
     * @return bool True if signature is valid
     * @throws \RuntimeException if no signer is configured
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (!$this->signer) {
            $this->logger->error('Attempted to verify webhook signature but no signer is configured.');

            throw new \RuntimeException('No signer available. Provide apiSecret at client init or a custom signer.');
        }

        $expectedSignature = $this->signer->sign($payload);
        $isValid = hash_equals($expectedSignature, $signature);

        $this->logger->debug('Webhook signature verification', ['valid' => $isValid, 'expected' => $expectedSignature, 'received' => $signature]);

        return $isValid;
    }

    /**
     * @return array<string, mixed>
     * @throws APIException
     */
    private function decodeJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            $this->logger->error('Invalid JSON response received from API', ['statusCode' => $response->getStatusCode(), 'body' => $body]);

            throw new APIException('Invalid JSON response', $response->getStatusCode(), $body, $response->getHeaders());
        }

        return $decoded;
    }

    /**
     * @throws APIException
     */
    private function rethrowApiException(GuzzleException $e): never
    {
        $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;
        $status = $response?->getStatusCode();
        $headers = $response?->getHeaders();
        $data = null;
        if ($response) {
            $raw = (string) $response->getBody();
            $json = json_decode($raw, true);
            $data = $json ?? $raw;
        }
        $message = is_array($data) && isset($data['message']) ? $data['message'] : $e->getMessage();

        throw new APIException($message ?: 'Request failed', $status, $data, $headers, $e);
    }
}
