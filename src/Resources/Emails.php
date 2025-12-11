<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Emails
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function send(array $params): array
    {
        return $this->client->post('/emails', $params);
    }
}
