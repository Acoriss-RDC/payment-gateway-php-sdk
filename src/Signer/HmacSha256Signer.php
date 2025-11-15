<?php

namespace Acoriss\PaymentGateway\Signer;

class HmacSha256Signer implements SignerInterface
{
  public function __construct(private readonly string $secret) {}

  public function sign(string $body): string
  {
    return hash_hmac('sha256', $body, $this->secret);
  }
}
