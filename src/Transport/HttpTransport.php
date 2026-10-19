<?php

declare(strict_types=1);

namespace Sevk\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use JsonException;
use Sevk\Exceptions\SevkException;
use Sevk\Sevk;

final class HttpTransport implements Transport
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_CONNECT_TIMEOUT = 10;

    private readonly Client $client;

    public function __construct(string $apiKey, array $options = [])
    {
        $clientOptions = [
            'base_uri' => rtrim($options['baseUrl'] ?? 'https://api.sevk.io', '/') . '/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'sevk-php/' . Sevk::VERSION,
            ],
            'http_errors' => false,
            'allow_redirects' => false,
            'timeout' => $options['timeout'] ?? self::DEFAULT_TIMEOUT,
            'connect_timeout' => $options['connectTimeout'] ?? self::DEFAULT_CONNECT_TIMEOUT,
        ];
        if (array_key_exists('handler', $options)) {
            $clientOptions['handler'] = $options['handler'];
        }
        $this->client = new Client($clientOptions);
    }

    public function get(string $path, ?array $query = null): array
    {
        return $this->request('GET', $path, query: $query);
    }

    public function post(string $path, ?array $data = null, ?array $headers = null): array
    {
        return $this->request('POST', $path, $data, headers: $headers);
    }

    public function put(string $path, ?array $data = null): array
    {
        return $this->request('PUT', $path, $data);
    }

    public function patch(string $path, ?array $data = null): array
    {
        return $this->request('PATCH', $path, $data);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    private function request(
        string $method,
        string $path,
        ?array $data = null,
        ?array $query = null,
        ?array $headers = null
    ): array {
        $options = [];

        if ($data !== null) {
            try {
                $options['body'] = json_encode((object) $data, JSON_THROW_ON_ERROR);
            } catch (JsonException $error) {
                throw new SevkException(
                    'Request body cannot be JSON encoded: ' . $error->getMessage(),
                    0,
                    'SERIALIZATION_ERROR',
                    previous: $error
                );
            }
        }
        if ($query !== null) {
            $options['query'] = $query;
        }
        if ($headers !== null && $headers !== []) {
            $options['headers'] = $headers;
        }

        try {
            $response = $this->client->request($method, ltrim($path, '/'), $options);
        } catch (TransferException $error) {
            $context = $error instanceof ConnectException || $error instanceof RequestException
                ? $error->getHandlerContext()
                : [];
            throw new SevkException(
                $error->getMessage(),
                0,
                ($context['errno'] ?? null) === 28 ? 'TIMEOUT' : 'NETWORK_ERROR',
                previous: $error
            );
        }
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $decodedBody = json_decode($body, true);
        $decodedErrorBody = json_decode($body);
        $decoded = json_last_error() === JSON_ERROR_NONE;

        if ($statusCode < 200 || $statusCode >= 300) {
            throw SevkException::fromResponse(
                $statusCode,
                $response->getHeaders(),
                $decoded ? $decodedErrorBody : $body,
                $decoded ? null : $body
            );
        }

        if ($statusCode === 204) {
            return [];
        }
        if (!is_array($decodedBody) || !is_object($decodedErrorBody)) {
            throw new SevkException(
                'Expected a JSON response body but received an empty or non-JSON response',
                $statusCode,
                'INVALID_RESPONSE',
                $body
            );
        }

        return $decodedBody;
    }
}
