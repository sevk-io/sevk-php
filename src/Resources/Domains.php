<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Domains
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function list(?bool $verified = null): array
    {
        $query = [];
        if ($verified !== null) $query['verified'] = $verified ? 'true' : 'false';

        return $this->client->get('/domains', $query ?: null);
    }

    public function get(string $domainId): array
    {
        return $this->client->get("/domains/{$domainId}");
    }

    public function create(array $params): array
    {
        return $this->client->post('/domains', $params);
    }

    public function update(string $domainId, array $params): array
    {
        return $this->client->put("/domains/{$domainId}", $params);
    }

    public function delete(string $domainId): array
    {
        return $this->client->delete("/domains/{$domainId}");
    }

    public function verify(string $domainId): array
    {
        return $this->client->post("/domains/{$domainId}/verify");
    }

    public function getDnsRecords(string $domainId): array
    {
        return $this->client->get("/domains/{$domainId}/dns-records");
    }

    public function getRegions(): array
    {
        return $this->client->get('/domains/regions');
    }
}
