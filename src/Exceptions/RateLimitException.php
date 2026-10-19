<?php

declare(strict_types=1);

namespace Sevk\Exceptions;

class RateLimitException extends SevkException
{
    public readonly ?int $retryAfter;

    public function __construct(
        string $message,
        int $statusCode = 429,
        string $errorCode = 'TOO_MANY_REQUESTS',
        mixed $responseBody = null,
        ?int $retryAfter = null,
        array $fields = [],
        mixed $details = null
    ) {
        parent::__construct($message, $statusCode, $errorCode, $responseBody, $fields, $details);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
