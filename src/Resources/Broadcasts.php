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
}
