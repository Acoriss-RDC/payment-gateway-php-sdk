<?php

namespace Acoriss\PaymentGateway\Exceptions;

use Throwable;

class APIException extends \RuntimeException
{
    private ?int $status;
    private mixed $data;
    /** @var array<string, list<string>>|null */
    private ?array $headers;

    /**
     * @param array<string, list<string>>|null $headers
     */
    public function __construct(string $message, ?int $status = null, mixed $data = null, ?array $headers = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->status = $status;
        $this->data = $data;
        $this->headers = $headers;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @return array<string, list<string>>|null
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }
}
