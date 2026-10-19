<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type TopicBase array{
 *   id: string,
 *   name: string,
 *   description: string|null,
 *   audienceId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type Topic array{
 *   id: string,
 *   name: string,
 *   description: string|null,
 *   audienceId: string,
 *   createdAt: string,
 *   updatedAt: string,
 *   contactsCount: int,
 *   subscribedContactsCount: int,
 *   broadcastsCount: int
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
class Topics extends Resource
{
    /**
     * @return array{
     *   items: list<Topic>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(string $audienceId, ?int $page = null, ?int $limit = null, ?string $search = null, ?string $after = null, ?string $before = null): array
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

        return $this->client->get("/audiences/{$audienceId}/topics", $query ?: null);
    }

    /** @return TopicBase */
    public function create(string $audienceId, string $name, ?string $description = null): array
    {
        $data = ['name' => $name];

        if ($description !== null) {
            $data['description'] = $description;
        }

        return $this->client->post("/audiences/{$audienceId}/topics", $data);
    }

    /** @return Topic */
    public function get(string $topicId): array
    {
        return $this->client->get("/topics/{$topicId}");
    }

    /** @return TopicBase */
    public function update(string $topicId, ?string $name = null, ?string $description = null): array
    {
        $data = [];

        if ($name !== null) {
            $data['name'] = $name;
        }
        if ($description !== null) {
            $data['description'] = $description;
        }

        return $this->client->put("/topics/{$topicId}", $data);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $topicId): array
    {
        return $this->client->delete("/topics/{$topicId}");
    }

    /**
     * @param list<string> $contactIds
     * @return array{
     *   added: int,
     *   contacts: list<array{id: string, email: string, subscribed: bool, createdAt: string}>
     * }
     */
    public function addContacts(string $topicId, array $contactIds): array
    {
        return $this->client->post("/topics/{$topicId}/contacts", ['contactIds' => $contactIds]);
    }

    /** @return array{topicId: string, contactId: string, removed: true} */
    public function removeContact(string $topicId, string $contactId): array
    {
        return $this->client->delete("/topics/{$topicId}/contacts/{$contactId}");
    }

    /**
     * @param array{
     *   page?: int,
     *   limit?: int,
     *   after?: string,
     *   before?: string,
     *   search?: string,
     *   subscribed?: bool
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
    public function listContacts(string $topicId, array $params = []): array
    {
        if (($params['subscribed'] ?? null) === null) {
            unset($params['subscribed']);
        } elseif (is_bool($params['subscribed'])) {
            $params['subscribed'] = $params['subscribed'] ? 'true' : 'false';
        }

        return $this->client->get("/topics/{$topicId}/contacts", $params ?: null);
    }
}
