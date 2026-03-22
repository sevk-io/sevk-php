<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Webhooks
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function list(?int $page = null, ?int $limit = null): array
    {
        $query = [];
        if ($page !== null) $query['page'] = $page;
        if ($limit !== null) $query['limit'] = $limit;

        return $this->client->get('/webhooks', $query ?: null);
    }

    public function get(string $webhookId): array
    {
        return $this->client->get("/webhooks/{$webhookId}");
    }

    public function create(string $url, array $events): array
    {
        return $this->client->post('/webhooks', ['url' => $url, 'events' => $events]);
    }

    public function update(string $webhookId, array $params): array
    {
        return $this->client->put("/webhooks/{$webhookId}", $params);
    }

    public function delete(string $webhookId): array
    {
        return $this->client->delete("/webhooks/{$webhookId}");
    }

    public function test(string $webhookId): array
    {
        return $this->client->post("/webhooks/{$webhookId}/test");
    }

    public function listEvents(?string $webhookId = null, ?int $page = null, ?int $limit = null): array
    {
        $query = [];
        if ($page !== null) $query['page'] = $page;
        if ($limit !== null) $query['limit'] = $limit;

        if ($webhookId !== null) {
            return $this->client->get("/webhooks/{$webhookId}/events", $query ?: null);
        }

        return $this->client->get('/webhooks/events', $query ?: null);
    }
}
