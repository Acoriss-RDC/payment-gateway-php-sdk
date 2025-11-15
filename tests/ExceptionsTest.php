<?php

namespace Acoriss\PaymentGateway\Tests;

use Acoriss\PaymentGateway\Exceptions\APIException;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
  public function testApiExceptionBasic(): void
  {
    $e = new APIException('Oops');
    $this->assertSame('Oops', $e->getMessage());
    $this->assertNull($e->getStatus());
    $this->assertNull($e->getData());
    $this->assertNull($e->getHeaders());
  }

  public function testApiExceptionWithDetails(): void
  {
    $e = new APIException('Not found', 404, ['code' => 'NOT_FOUND'], ['x-request-id' => ['123']]);
    $this->assertSame(404, $e->getStatus());
    $this->assertSame(['code' => 'NOT_FOUND'], $e->getData());
    $this->assertSame(['x-request-id' => ['123']], $e->getHeaders());
  }
}
