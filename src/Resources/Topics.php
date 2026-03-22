<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Topics
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function list(string $audienceId, ?int $page = null, ?int $limit = null): array
    {
        $query = [];
        if ($page !== null) $query['page'] = $page;
        if ($limit !== null) $query['limit'] = $limit;

        return $this->client->get("/audiences/{$audienceId}/topics", $query ?: null);
    }

    public function create(string $audienceId, string $name, ?string $description = null): array
    {
        $data = ['name' => $name];
        if ($description !== null) $data['description'] = $description;

        return $this->client->post("/audiences/{$audienceId}/topics", $data);
    }

    public function get(string $audienceId, string $topicId): array
    {
        return $this->client->get("/audiences/{$audienceId}/topics/{$topicId}");
    }

    public function update(string $audienceId, string $topicId, ?string $name = null, ?string $description = null): array
    {
        $data = [];
        if ($name !== null) $data['name'] = $name;
        if ($description !== null) $data['description'] = $description;

        return $this->client->put("/audiences/{$audienceId}/topics/{$topicId}", $data);
    }

    public function delete(string $audienceId, string $topicId): array
    {
        return $this->client->delete("/audiences/{$audienceId}/topics/{$topicId}");
    }

    public function addContacts(string $audienceId, string $topicId, array $params): array
    {
        return $this->client->post("/audiences/{$audienceId}/topics/{$topicId}/contacts", $params);
    }

    public function removeContact(string $audienceId, string $topicId, string $contactId): array
    {
        return $this->client->delete("/audiences/{$audienceId}/topics/{$topicId}/contacts/{$contactId}");
    }

    public function listContacts(string $audienceId, string $topicId, array $params = []): array
    {
        return $this->client->get("/audiences/{$audienceId}/topics/{$topicId}/contacts", $params ?: null);
    }
}
