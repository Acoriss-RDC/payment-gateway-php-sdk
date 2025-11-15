<?php

namespace Acoriss\PaymentGateway;

/**
 * This file documents array shapes used by the SDK. These are not enforced runtime, but provided
 * for IDE assistance via PHPDoc. You can choose to create DTO classes later if stricter typing is desired.
 */
final class Types
{
  /**
   * @phpstan-type CustomerInfo array{email:string,name:string,phone?:string}
   * @phpstan-type ServiceItem array{name:string,price:int,description?:string,quantity?:int}
   * @phpstan-type PaymentSessionRequest array{
   *   amount:int,
   *   currency:string,
   *   customer:CustomerInfo,
   *   description?:string,
   *   callbackUrl?:string,
   *   cancelUrl?:string,
   *   successUrl?:string,
   *   transactionId?:string,
   *   services?:list<ServiceItem>,
   *   ...
   * }
   * @phpstan-type PaymentSessionResponse array{
   *   id:string,amount:int,currency:string,description?:string,checkoutUrl:string,customer:CustomerInfo,createdAt:string
   * }
   * @phpstan-type PaymentService array{
   *   id:string,name:string,description:string|null,quantity:int,price:int,currency:string|null,sessionId:string,createdAt:string
   * }
   * @phpstan-type RetrievePaymentResponse array{
   *   id:string,amount:int,currency:string,description:string|null,transactionId:string,customer:array{email:string|null,phone:string|null},createdAt:string,expired:bool,services:list<PaymentService>,status:string
   * }
   */
}
