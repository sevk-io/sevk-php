<?php

declare(strict_types=1);

namespace Sevk\Exceptions;

class SevkException extends \Exception
{
    public readonly int $statusCode;
    public readonly string $errorCode;
    public readonly mixed $responseBody;
    public readonly array $fields;
    public readonly mixed $details;

    public function __construct(
        string $message,
        int $statusCode = 0,
        string $errorCode = 'UNKNOWN_ERROR',
        mixed $responseBody = null,
        array $fields = [],
        mixed $details = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->responseBody = $responseBody;
        $this->fields = $fields;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }

    public static function fromResponse(
        int $status,
        array $headers,
        mixed $body,
        ?string $rawBody = null
    ): SevkException
    {
        $payload = [];

        if (is_object($body)) {
            $decoded = get_object_vars($body);

            if (isset($decoded['error']) && is_object($decoded['error'])) {
                $payload = get_object_vars($decoded['error']);
            } else {
                $payload = $decoded;
            }
        } elseif (is_array($body) && !array_is_list($body)) {
            if (isset($body['error']) && is_array($body['error']) && !array_is_list($body['error'])) {
                $payload = $body['error'];
            } else {
                $payload = $body;
            }
        }

        $rawCode = $payload['code'] ?? null;
        $code = is_string($rawCode) && $rawCode !== '' ? $rawCode : 'UNKNOWN_ERROR';
        $rawMessage = $payload['message'] ?? null;
        $message = is_string($rawMessage) && $rawMessage !== ''
            ? $rawMessage
            : (is_string($body) && trim($body) !== '' ? trim($body) : "HTTP {$status}");

        /** @var array<string, list<string>> $fields */
        $fields = [];
        $rawFields = $payload['fields'] ?? null;

        if (is_object($rawFields)) {
            $rawFields = get_object_vars($rawFields);
        }
        if (is_array($rawFields) && !array_is_list($rawFields)) {
            foreach ($rawFields as $key => $value) {
                if (is_array($value)) {
                    $fields[(string) $key] = array_values(
                        array_map(static fn ($v) => (string) $v, $value)
                    );
                } else {
                    $fields[(string) $key] = [(string) $value];
                }
            }
        }
        $details = $payload['details'] ?? null;
        $responseBody = $rawBody ?? $body;

        if ($status === 422 || ($status === 400 && ($code === 'VALIDATION_ERROR' || $fields !== []))) {
            return new ValidationException(
                $message,
                $status,
                $code,
                $responseBody,
                $fields,
                $details
            );
        }

        return match (true) {
            $status === 400 => new BadRequestException($message, $status, $code, $responseBody, $fields, $details),
            $status === 401 => new UnauthorizedException($message, $status, $code, $responseBody, $fields, $details),
            $status === 403 => new ForbiddenException($message, $status, $code, $responseBody, $fields, $details),
            $status === 404 => new NotFoundException($message, $status, $code, $responseBody, $fields, $details),
            $status === 409 => new ConflictException($message, $status, $code, $responseBody, $fields, $details),
            $status === 429 => new RateLimitException(
                $message,
                $status,
                $code,
                $responseBody,
                self::parseRetryAfter($headers),
                $fields,
                $details
            ),
            $status >= 500 && $status <= 599 => new InternalServerErrorException(
                $message,
                $status,
                $code,
                $responseBody,
                $fields,
                $details
            ),
            default => new SevkException($message, $status, $code, $responseBody, $fields, $details),
        };
    }

    private static function parseRetryAfter(array $headers): ?int
    {
        $value = null;

        foreach ($headers as $name => $headerValue) {
            if (strtolower((string) $name) === 'retry-after') {
                $value = is_array($headerValue) ? ($headerValue[0] ?? null) : $headerValue;
                break;
            }
        }

        if (!is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        if (preg_match('/^\d+$/D', $text) !== 1) {
            return null;
        }

        $normalized = ltrim($text, '0');

        if ($normalized === '') {
            return 0;
        }

        $max = '2147483647';

        if (strlen($normalized) > strlen($max) || (
            strlen($normalized) === strlen($max) && strcmp($normalized, $max) > 0
        )) {
            return null;
        }

        return (int) $normalized;
    }
}
