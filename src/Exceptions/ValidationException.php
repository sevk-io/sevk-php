<?php

declare(strict_types=1);

namespace Sevk\Exceptions;

class ValidationException extends SevkException
{
    /**
     * @param array<string, list<string>> $fields
     */
    public function __construct(
        string $message,
        int $statusCode = 0,
        string $errorCode = 'VALIDATION_ERROR',
        mixed $responseBody = null,
        array $fields = [],
        mixed $details = null
    ) {
        parent::__construct($message, $statusCode, $errorCode, $responseBody, $fields, $details);
    }
}
