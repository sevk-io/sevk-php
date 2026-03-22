<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Contacts
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

        return $this->client->get('contacts', $query ?: null);
    }

    public function create(string $email, ?bool $subscribed = null, ?array $metadata = null): array
    {
        $data = ['email' => $email];
        if ($subscribed !== null) $data['subscribed'] = $subscribed;
        if ($metadata !== null) $data['metadata'] = $metadata;

        return $this->client->post('contacts', $data);
    }

    public function get(string $contactId): array
    {
        return $this->client->get("contacts/{$contactId}");
    }

    public function update(string $contactId, ?bool $subscribed = null, ?array $metadata = null): array
    {
        $data = [];
        if ($subscribed !== null) $data['subscribed'] = $subscribed;
        if ($metadata !== null) $data['metadata'] = $metadata;

        return $this->client->put("contacts/{$contactId}", $data);
    }

    public function delete(string $contactId): array
    {
        return $this->client->delete("contacts/{$contactId}");
    }

    public function bulkUpdate(array $updates): array
    {
        return $this->client->put('contacts/bulk-update', $updates);
    }

    public function import(array $params): array
    {
        return $this->client->post('contacts/import', $params);
    }

    public function getEvents(string $contactId): array
    {
        return $this->client->get("contacts/{$contactId}/events");
    }
}
