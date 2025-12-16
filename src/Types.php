<?php

namespace Acoriss\PaymentGateway;

/**
 * This file documents array shapes used by the SDK. These are not enforced at runtime, but provided
 * for IDE assistance via PHPDoc. You can choose to create DTO classes later if stricter typing is desired.
 */
final class Types
{
    /**
     * Client configuration shape
     * @phpstan-type Config array{
     *   apiKey:string,
     *   apiSecret?:string,
     *   environment?:string,
     *   baseUrl?:string,
     *   signer?:\Acoriss\PaymentGateway\Signer\SignerInterface,
     *   timeout?:float,
     *   httpClient?:\GuzzleHttp\Client,
     *   logger?:\Psr\Log\LoggerInterface,
     *   retries?:int,
     *   verify?:bool|string
     * }
     *
     * Customer information
     * @phpstan-type CustomerInfo array{email:string,name:string,phone?:string}
     *
     * Service/product item
     * @phpstan-type ServiceItem array{name:string,price:int,description?:string,quantity?:int}
     *
     * Payment session request payload
     * @phpstan-type PaymentSessionRequest array{
     *   amount:int,
     *   currency:string,
     *   customer:CustomerInfo,
     *   description?:string,
     *   callbackUrl?:string,
     *   cancelUrl?:string,
     *   successUrl?:string,
     *   transactionId?:string,
     *   serviceId?: string,
     *   services?:list<ServiceItem>,
     *   ...
     * }
     *
     * Payment session API response
     * @phpstan-type PaymentSessionResponse array{
     *   id:string,
     *   amount:int,
     *   currency:string,
     *   description?:string,
     *   checkoutUrl:string,
     *   customer:CustomerInfo,
     *   createdAt:string,
     *   serviceId?: string,
     * }
     *
     * Payment service from API
     * @phpstan-type PaymentService array{
     *   id:string,
     *   name:string,
     *   description:string|null,
     *   quantity:int,
     *   price:int,
     *   currency:string|null,
     *   sessionId:string,
     *   createdAt:string
     * }
     *
     * Payment retrieval API response
     * @phpstan-type RetrievePaymentResponse array{
     *   id:string,
     *   amount:int,
     *   currency:string,
     *   description:string|null,
     *   transactionId:string,
     *   customer:array{email:string|null,phone:string|null},
     *   createdAt:string,
     *   expired:bool,
     *   services:list<PaymentService>,
     *   status:string
     *   serviceId?: string,
     * }
     */
}
