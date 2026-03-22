<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Broadcasts
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function list(?int $page = null, ?int $limit = null, ?string $search = null): array
    {
        $query = [];
        if ($page !== null) $query['page'] = $page;
        if ($limit !== null) $query['limit'] = $limit;
        if ($search !== null) $query['search'] = $search;

        return $this->client->get('/broadcasts', $query ?: null);
    }

    public function get(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}");
    }

    public function create(array $params): array
    {
        return $this->client->post('/broadcasts', $params);
    }

    public function update(string $broadcastId, array $params): array
    {
        return $this->client->put("/broadcasts/{$broadcastId}", $params);
    }

    public function delete(string $broadcastId): array
    {
        return $this->client->delete("/broadcasts/{$broadcastId}");
    }

    public function send(string $broadcastId): array
    {
        return $this->client->post("/broadcasts/{$broadcastId}/send");
    }

    public function cancel(string $broadcastId): array
    {
        return $this->client->post("/broadcasts/{$broadcastId}/cancel");
    }

    public function sendTest(string $broadcastId, array $params): array
    {
        return $this->client->post("/broadcasts/{$broadcastId}/test", $params);
    }

    public function getAnalytics(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}/analytics");
    }

    public function getStatus(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}/status");
    }

    public function getEmails(string $broadcastId, array $params = []): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}/emails", $params ?: null);
    }

    public function estimateCost(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}/estimate-cost");
    }

    public function listActive(): array
    {
        return $this->client->get('/broadcasts/active');
    }
}
