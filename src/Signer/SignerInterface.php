<?php

namespace Acoriss\PaymentGateway\Signer;

interface SignerInterface
{
  /**
   * Returns a hexadecimal HMAC or any signature string for the given body.
   */
  public function sign(string $body): string;
}
