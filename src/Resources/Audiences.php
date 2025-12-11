<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Audiences
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

        return $this->client->get('/audiences', $query ?: null);
    }

    public function create(string $name, ?string $description = null, ?string $usersCanSee = null): array
    {
        $data = ['name' => $name];
        if ($description !== null) $data['description'] = $description;
        if ($usersCanSee !== null) $data['usersCanSee'] = $usersCanSee;

        return $this->client->post('/audiences', $data);
    }

    public function get(string $audienceId): array
    {
        return $this->client->get("/audiences/{$audienceId}");
    }

    public function update(string $audienceId, ?string $name = null, ?string $description = null, ?string $usersCanSee = null): array
    {
        $data = [];
        if ($name !== null) $data['name'] = $name;
        if ($description !== null) $data['description'] = $description;
        if ($usersCanSee !== null) $data['usersCanSee'] = $usersCanSee;

        return $this->client->put("/audiences/{$audienceId}", $data);
    }

    public function delete(string $audienceId): array
    {
        return $this->client->delete("/audiences/{$audienceId}");
    }

    public function addContacts(string $audienceId, array $contactIds): array
    {
        return $this->client->post("/audiences/{$audienceId}/contacts", ['contactIds' => $contactIds]);
    }
}
