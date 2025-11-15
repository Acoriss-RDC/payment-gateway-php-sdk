<?php

namespace Acoriss\PaymentGateway;

use Acoriss\PaymentGateway\Exceptions\APIException;
use Acoriss\PaymentGateway\Signer\SignerInterface;
use Acoriss\PaymentGateway\Signer\HmacSha256Signer;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

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

  /**
   * @param array{apiKey:string,apiSecret?:string,environment?:string,baseUrl?:string,signer?:SignerInterface,timeout?:float,httpClient?:GuzzleClient} $config
   */
  public function __construct(array $config)
  {
    $environment = $config['environment'] ?? 'sandbox';
    $baseUrl = $config['baseUrl'] ?? self::BASE_URLS[$environment] ?? self::BASE_URLS['sandbox'];
    $this->apiKey = $config['apiKey'];
    $this->signer = $config['signer'] ?? (isset($config['apiSecret']) ? new HmacSha256Signer($config['apiSecret']) : null);

    $this->http = $config['httpClient'] ?? new GuzzleClient([
      'base_uri' => $baseUrl,
      'timeout' => $config['timeout'] ?? 15.0,
      'headers' => [
        'Content-Type' => 'application/json',
      ],
    ]);
  }

  /**
   * @param array $payload Payment session request shape (see README)
   * @param array{signatureOverride?:string} $opts
   * @return array Decoded JSON payment session response
   */
  public function createSession(array $payload, array $opts = []): array
  {
    $rawBody = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($rawBody === false) {
      throw new \InvalidArgumentException('Failed to encode payload to JSON');
    }
    $signature = $opts['signatureOverride'] ?? ($this->signer?->sign($rawBody));
    if (!$signature) {
      throw new \RuntimeException('No signature available. Provide apiSecret at client init, a custom signer, or pass signatureOverride.');
    }

    try {
      $response = $this->http->post('sessions', [
        'body' => $rawBody,
        'headers' => [
          'X-API-KEY' => $this->apiKey,
          'X-SIGNATURE' => $signature,
        ],
      ]);
      return $this->decodeJson($response);
    } catch (GuzzleException $e) {
      $this->rethrowApiException($e);
    }
  }

  /**
   * @param string $paymentId
   * @param array{signatureOverride?:string} $opts
   * @return array Decoded JSON retrieve payment response
   */
  public function getPayment(string $paymentId, array $opts = []): array
  {
    $signature = $opts['signatureOverride'] ?? ($this->signer?->sign($paymentId));
    if (!$signature) {
      throw new \RuntimeException('No signature available. Provide apiSecret at client init, a custom signer, or pass signatureOverride.');
    }

    try {
      $response = $this->http->get('sessions/' . rawurlencode($paymentId), [
        'headers' => [
          'X-API-KEY' => $this->apiKey,
          'X-SIGNATURE' => $signature,
        ],
      ]);
      return $this->decodeJson($response);
    } catch (GuzzleException $e) {
      $this->rethrowApiException($e);
    }
  }

  private function decodeJson(ResponseInterface $response): array
  {
    $body = (string) $response->getBody();
    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
      throw new APIException('Invalid JSON response', $response->getStatusCode(), $body, $response->getHeaders());
    }
    return $decoded;
  }

  private function rethrowApiException(GuzzleException $e): void
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
