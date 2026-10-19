<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type EventType 'email.sent'|'email.delivered'|'email.opened'|'email.clicked'|'email.bounced'|'email.complained'|'email.rejected'|'email.delayed'|'email.rendering_failed'|'email.received'|'contact.subscribed'|'contact.unsubscribed'|'broadcast.started'|'broadcast.finished'
 * @phpstan-type EventDefinition array{name: string, description: string, category: 'email'|'subscription'|'broadcast'}
 * The Webhook secret field is a masked placeholder (••••••••) outside the
 * one-time create response and cannot be used for signature verification.
 * @phpstan-type Webhook array{
 *   id: string,
 *   url: string,
 *   events: list<EventType>,
 *   enabled: bool,
 *   secret: string|null,
 *   failureCount: int,
 *   lastFailureAt: string|null,
 *   lastDeliveryAt: string|null,
 *   disabledAt: string|null,
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type CreatedWebhook array{
 *   id: string,
 *   url: string,
 *   events: list<EventType>,
 *   enabled: bool,
 *   secret: string,
 *   failureCount: int,
 *   lastFailureAt: string|null,
 *   lastDeliveryAt: string|null,
 *   disabledAt: string|null,
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 */
class Webhooks extends Resource
{
    /** @return array{id: string, secret: string} */
    public function rotate(string $webhookId): array
    {
        return $this->client->post("/webhooks/{$webhookId}/rotate");
    }

    /**
     * @return array{
     *   items: list<Webhook>,
     *   availableEvents: array<string, EventDefinition>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(?int $page = null, ?int $limit = null, ?string $after = null, ?string $before = null): array
    {
        $query = array_filter([
            'page' => $page,
            'limit' => $limit,
            'after' => $after,
            'before' => $before,
        ], static fn ($value) => $value !== null);

        return $this->client->get('/webhooks', $query ?: null);
    }

    /** @return Webhook */
    public function get(string $webhookId): array
    {
        return $this->client->get("/webhooks/{$webhookId}");
    }

    /**
     * @param list<EventType> $events
     * @return CreatedWebhook The usable whsec_ signing secret is guaranteed exactly once here.
     */
    public function create(string $url, array $events, ?bool $enabled = null): array
    {
        $payload = ['url' => $url, 'events' => $events];

        if ($enabled !== null) {
            $payload['enabled'] = $enabled;
        }

        return $this->client->post('/webhooks', $payload);
    }

    /**
     * @param array{url?: string, events?: list<EventType>, enabled?: bool} $params
     * @return Webhook
     */
    public function update(string $webhookId, array $params): array
    {
        return $this->client->put("/webhooks/{$webhookId}", $params);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $webhookId): array
    {
        return $this->client->delete("/webhooks/{$webhookId}");
    }

    /** @return array{success: bool, status: int, statusText: string, message: string} */
    public function test(string $webhookId): array
    {
        return $this->client->post("/webhooks/{$webhookId}/test");
    }

    /** @return array{items: list<EventType>, events: array<string, EventDefinition>} */
    public function events(): array
    {
        return $this->client->get('/webhooks/events');
    }
}
