<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type Contact array{
 *   id: string,
 *   email: string,
 *   subscribed: bool,
 *   data: array<array-key, mixed>|null,
 *   unsubscribedAt: string|null,
 *   unsubscribedBy: 'USER'|'BOUNCE'|'COMPLAINT'|'API'|'ADMIN'|null,
 *   resubscriptionLocked: bool,
 *   bounceCount: int,
 *   lastBouncedAt: string|null,
 *   lastEventAt: string|null,
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type ContactActivity array{
 *   id: string,
 *   action: 'REQUESTED'|'SENT'|'DELIVERED'|'OPENED'|'CLICKED'|'BOUNCED'|'COMPLAINT'|'FAILED'|'SUBSCRIBED'|'UNSUBSCRIBED'|'DELAYED'|'REJECTED',
 *   description: string|null,
 *   createdAt: string,
 *   userAgent: string|null,
 *   link: string|null,
 *   metadata: mixed,
 *   broadcast: array{id: string, name: string|null}|null,
 *   email: array{id: string, subject: string|null}|null
 * }
 */
class Contacts extends Resource
{
    /**
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
    public function list(?int $page = null, ?int $limit = null, ?string $search = null, ?string $excludeAudience = null, ?bool $subscribed = null, ?string $after = null, ?string $before = null): array
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
        if ($excludeAudience !== null) {
            $query['excludeAudience'] = $excludeAudience;
        }
        if ($subscribed !== null) {
            $query['subscribed'] = $subscribed ? 'true' : 'false';
        }

        return $this->client->get('contacts', $query ?: null);
    }

    /**
     * @param array<array-key, mixed>|null $data
     * @return Contact
     */
    public function create(string $email, ?bool $subscribed = null, ?array $data = null): array
    {
        $payload = ['email' => $email];

        if ($subscribed !== null) {
            $payload['subscribed'] = $subscribed;
        }
        if ($data !== null) {
            $payload['data'] = (object) $data;
        }

        return $this->client->post('contacts', $payload);
    }

    /** @return Contact */
    public function get(string $contactId): array
    {
        return $this->client->get("contacts/{$contactId}");
    }

    /**
     * @param array<array-key, mixed>|false|null $data
     * @return Contact
     */
    public function update(string $contactId, ?string $email = null, ?bool $subscribed = null, array|false|null $data = false): array
    {
        $payload = [];

        if ($email !== null) {
            $payload['email'] = $email;
        }
        if ($subscribed !== null) {
            $payload['subscribed'] = $subscribed;
        }
        if ($data !== false) {
            $payload['data'] = is_array($data) ? (object) $data : $data;
        }

        return $this->client->put("contacts/{$contactId}", $payload);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $contactId): array
    {
        return $this->client->delete("contacts/{$contactId}");
    }

    /**
     * @param array{contacts: list<array{
     *   email: string,
     *   subscribed?: bool,
     *   data?: array<array-key, mixed>|null
     * }>} $updates
     * @return array{
     *   updated: int,
     *   notFound: int,
     *   errors: list<string>,
     *   updatedEmails: list<string>,
     *   notFoundEmails: list<string>
     * }
     */
    public function bulkUpdate(array $updates): array
    {
        if (isset($updates['contacts']) && is_array($updates['contacts'])) {
            foreach ($updates['contacts'] as &$contact) {
                if (is_array($contact) && isset($contact['data']) && is_array($contact['data'])) {
                    $contact['data'] = (object) $contact['data'];
                }
            }
            unset($contact);
        }

        return $this->client->put('contacts/bulk-update', $updates);
    }

    /**
     * @param array{
     *   contacts: list<array{email: string, subscribed?: bool, data?: array<array-key, mixed>}>,
     *   audienceId?: string,
     *   topicId?: string
     * } $params
     * @return array{
     *   created: int,
     *   errors: list<string>,
     *   contacts: list<array{id: string, email: string, subscribed: bool}>
     * }
     */
    public function bulkImport(array $params): array
    {
        if (isset($params['contacts']) && is_array($params['contacts'])) {
            foreach ($params['contacts'] as &$contact) {
                if (is_array($contact) && isset($contact['data']) && is_array($contact['data'])) {
                    $contact['data'] = (object) $contact['data'];
                }
            }
            unset($contact);
        }

        return $this->client->post('contacts/import', $params);
    }

    /**
     * @return array{
     *   items: list<ContactActivity>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function activity(string $contactId, ?int $page = null, ?int $limit = null, ?string $after = null, ?string $before = null): array
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

        return $this->client->get("contacts/{$contactId}/activity", $query ?: null);
    }
}
