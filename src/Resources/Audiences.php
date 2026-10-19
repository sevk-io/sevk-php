<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type AudienceBase array{
 *   id: string,
 *   name: string,
 *   description: string|null,
 *   visibility: 'PUBLIC'|'PRIVATE',
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type Audience array{
 *   id: string,
 *   name: string,
 *   description: string|null,
 *   visibility: 'PUBLIC'|'PRIVATE',
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string,
 *   contactsCount: int,
 *   subscribedContactsCount: int,
 *   broadcastsCount: int,
 *   topicsCount: int,
 *   segmentsCount: int
 * }
 * @phpstan-type Contact array{
 *   id: string,
 *   email: string,
 *   subscribed: bool,
 *   data?: array<string, mixed>|null,
 *   unsubscribedAt?: string|null,
 *   unsubscribedBy?: 'USER'|'BOUNCE'|'COMPLAINT'|'API'|'ADMIN'|null,
 *   resubscriptionLocked: bool,
 *   bounceCount: int,
 *   lastBouncedAt?: string|null,
 *   lastEventAt?: string|null,
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 */
class Audiences extends Resource
{
    /**
     * @return array{
     *   items: list<Audience>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(?int $page = null, ?int $limit = null, ?string $search = null, ?string $after = null, ?string $before = null): array
    {
        $query = [];

        if ($page !== null) {
            $query['page'] = $page;
        }
        if ($limit !== null) {
            $query['limit'] = $limit;
        }
        if ($after !== null) {
            $query['after'] = $after;
        }
        if ($before !== null) {
            $query['before'] = $before;
        }
        if ($search !== null) {
            $query['search'] = $search;
        }

        return $this->client->get('/audiences', $query ?: null);
    }

    /**
     * @param 'PUBLIC'|'PRIVATE'|null $visibility
     * @return AudienceBase
     */
    public function create(string $name, ?string $description = null, ?string $visibility = null): array
    {
        $data = ['name' => $name];

        if ($description !== null) {
            $data['description'] = $description;
        }
        if ($visibility !== null) {
            $data['visibility'] = $visibility;
        }

        return $this->client->post('/audiences', $data);
    }

    /** @return Audience */
    public function get(string $audienceId): array
    {
        return $this->client->get("/audiences/{$audienceId}");
    }

    /**
     * @param 'PUBLIC'|'PRIVATE'|null $visibility
     * @return AudienceBase
     */
    public function update(string $audienceId, ?string $name = null, ?string $description = null, ?string $visibility = null): array
    {
        $data = [];

        if ($name !== null) {
            $data['name'] = $name;
        }
        if ($description !== null) {
            $data['description'] = $description;
        }
        if ($visibility !== null) {
            $data['visibility'] = $visibility;
        }

        return $this->client->put("/audiences/{$audienceId}", $data);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $audienceId): array
    {
        return $this->client->delete("/audiences/{$audienceId}");
    }

    /**
     * @param list<string> $contactIds
     * @return array{
     *   added: int,
     *   contacts: list<array{id: string, email: string, subscribed: bool, createdAt: string}>
     * }
     */
    public function addContacts(string $audienceId, array $contactIds): array
    {
        return $this->client->post("/audiences/{$audienceId}/contacts", ['contactIds' => $contactIds]);
    }

    /**
     * @param array{
     *   page?: int,
     *   limit?: int,
     *   after?: string,
     *   before?: string,
     *   search?: string,
     *   subscribed?: bool,
     *   excludeTopicId?: string
     * } $params
     * @return array{
     *   items: list<Contact>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function listContacts(string $audienceId, array $params = []): array
    {
        if (($params['subscribed'] ?? null) === null) {
            unset($params['subscribed']);
        } elseif (is_bool($params['subscribed'])) {
            $params['subscribed'] = $params['subscribed'] ? 'true' : 'false';
        }

        return $this->client->get("/audiences/{$audienceId}/contacts", $params ?: null);
    }

    /** @return array{audienceId: string, contactId: string, removed: true} */
    public function removeContact(string $audienceId, string $contactId): array
    {
        return $this->client->delete("/audiences/{$audienceId}/contacts/{$contactId}");
    }
}
