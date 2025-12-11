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
        if ($verified !== null) $query['verified'] = $verified;

        return $this->client->get('/domains', $query ?: null);
    }

    public function get(string $domainId): array
    {
        return $this->client->get("/domains/{$domainId}");
    }
}
