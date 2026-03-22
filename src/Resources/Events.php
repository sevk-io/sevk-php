<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Events
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function list(array $params = []): array
    {
        return $this->client->get('/events', $params ?: null);
    }

    public function stats(?string $from = null, ?string $to = null): array
    {
        $query = [];
        if ($from !== null) $query['from'] = $from;
        if ($to !== null) $query['to'] = $to;

        return $this->client->get('/events/stats', $query ?: null);
    }
}
