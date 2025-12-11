<?php

declare(strict_types=1);

namespace Sevk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sevk\Resources\Contacts;
use Sevk\Resources\Audiences;
use Sevk\Resources\Templates;
use Sevk\Resources\Broadcasts;
use Sevk\Resources\Domains;
use Sevk\Resources\Topics;
use Sevk\Resources\Segments;
use Sevk\Resources\Subscriptions;
use Sevk\Resources\Emails;

class Sevk
{
    private Client $client;
    private string $apiKey;
    private string $baseUrl;

    public Contacts $contacts;
    public Audiences $audiences;
    public Templates $templates;
    public Broadcasts $broadcasts;
    public Domains $domains;
    public Topics $topics;
    public Segments $segments;
    public Subscriptions $subscriptions;
    public Emails $emails;

    public function __construct(string $apiKey, ?array $options = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $options['baseUrl'] ?? 'https://api.sevk.io';
        // Ensure baseUrl ends with /
        if (!str_ends_with($this->baseUrl, '/')) {
            $this->baseUrl .= '/';
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'http_errors' => false,
        ]);

        $this->contacts = new Contacts($this);
        $this->audiences = new Audiences($this);
        $this->templates = new Templates($this);
        $this->broadcasts = new Broadcasts($this);
        $this->domains = new Domains($this);
        $this->topics = new Topics($this);
        $this->segments = new Segments($this);
        $this->subscriptions = new Subscriptions($this);
        $this->emails = new Emails($this);
    }

    public function request(string $method, string $path, ?array $data = null, ?array $query = null): array
    {
        // Remove leading slash from path for proper URL resolution
        $path = ltrim($path, '/');

        $options = [];

        if ($data !== null) {
            $options['json'] = $data;
        }

        if ($query !== null) {
            $options['query'] = $query;
        }

        $response = $this->client->request($method, $path, $options);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $result = json_decode($body, true) ?? [];

        if ($statusCode >= 400) {
            $message = $result['message'] ?? $result['error'] ?? "HTTP Error: {$statusCode}";
            // Handle case where message is an array (validation errors)
            if (is_array($message)) {
                $message = json_encode($message);
            }
            throw new \Exception("{$statusCode}: {$message}");
        }

        return $result;
    }

    public function get(string $path, ?array $query = null): array
    {
        return $this->request('GET', $path, null, $query);
    }

    public function post(string $path, ?array $data = null): array
    {
        return $this->request('POST', $path, $data);
    }

    public function put(string $path, ?array $data = null): array
    {
        return $this->request('PUT', $path, $data);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }
}
