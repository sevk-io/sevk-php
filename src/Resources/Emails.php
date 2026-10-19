<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Markup\Renderer;

/**
 * @phpstan-type Attachment array{
 *   filename: string,
 *   content: string,
 *   contentType: string,
 *   cid?: string
 * }
 * @phpstan-type EmailPayload array{
 *   from: string,
 *   to: string|list<string>,
 *   subject: string,
 *   html?: string|null,
 *   text?: string|null,
 *   markup?: string,
 *   replyTo?: string|list<string>,
 *   cc?: string|list<string>,
 *   bcc?: string|list<string>,
 *   attachments?: list<Attachment>,
 *   tracking?: array{open?: bool, click?: bool},
 *   headers?: array<string, string>,
 *   scheduledAt?: string
 * }
 * @phpstan-type BulkEmailPayload array{
 *   from: string,
 *   to: string,
 *   subject: string,
 *   html?: string|null,
 *   text?: string|null,
 *   markup?: string,
 *   replyTo?: string|list<string>,
 *   cc?: string|list<string>,
 *   bcc?: string|list<string>,
 *   attachments?: list<Attachment>,
 *   tracking?: array{open?: bool, click?: bool},
 *   headers?: array<string, string>
 * }
 */
class Emails extends Resource
{
    /**
     * @param EmailPayload|BulkEmailPayload $params
     * @return array<string, mixed>
     */
    private function toWire(array $params): array
    {
        if (array_key_exists('replyTo', $params)) {
            $params['reply_to'] = $params['replyTo'];
            unset($params['replyTo']);
        }

        if (array_key_exists('markup', $params)) {
            if (is_string($params['markup']) && $params['markup'] !== '') {
                $renderer = new Renderer();
                $params['html'] = $renderer->render($params['markup']);
            }
            unset($params['markup']);
        }

        foreach (['tracking', 'headers'] as $field) {
            if (($params[$field] ?? null) === []) {
                $params[$field] = (object) [];
            }
        }

        return $params;
    }

    /**
     * @param EmailPayload $params
     * @param array{idempotency_key?: string}|null $options
     * @return array{id: string}|array{ids: list<string>}
     */
    public function send(array $params, ?array $options = null): array
    {
        return $this->client->post('/emails', $this->toWire($params), $this->idempotencyHeaders($options));
    }

    /**
     * @return array{id: string}
     */
    public function update(string $emailId, string $scheduledAt): array
    {
        return $this->client->patch("/emails/{$emailId}", [
            'scheduledAt' => $scheduledAt,
        ]);
    }

    /**
     * @return array{id: string}
     */
    public function cancel(string $emailId): array
    {
        return $this->client->post("/emails/{$emailId}/cancel");
    }

    /**
     * @param list<BulkEmailPayload> $emails
     * @param array{idempotency_key?: string}|null $options
     * @return array{
     *   success: int,
     *   failed: int,
     *   ids: list<string>,
     *   errors?: list<array{index: int, email: string, error: string}>
     * }
     */
    public function sendBulk(array $emails, ?array $options = null): array
    {
        return $this->client->post('/emails/bulk', [
            'emails' => array_values(array_map(fn($e) => is_array($e) ? $this->toWire($e) : $e, $emails)),
        ], $this->idempotencyHeaders($options));
    }

    /**
     * @param array{idempotency_key?: string}|null $options
     * @return array{'Idempotency-Key': string}|null
     */
    private function idempotencyHeaders(?array $options): ?array
    {
        if ($options === null || !array_key_exists('idempotency_key', $options)) {
            return null;
        }

        return ['Idempotency-Key' => $options['idempotency_key']];
    }

    /**
     * @return array{
     *   id: string,
     *   messageId: string|null,
     *   providerMessageId: string|null,
     *   from: string|null,
     *   to: string|null,
     *   subject: string|null,
     *   body: string|null,
     *   text: string|null,
     *   status: 'PENDING'|'SCHEDULED'|'SENT'|'DELIVERED'|'BOUNCED'|'OPENED'|'COMPLAINT'|'FAILED'|'SUPPRESSED'|'CANCELLED',
     *   type: 'MARKETING'|'TRANSACTIONAL',
     *   userAgent: string|null,
     *   contactId: string|null,
     *   broadcastId: string|null,
     *   broadcastVariantId: string|null,
     *   projectId: string,
     *   openedAt: string|null,
     *   clickedAt: string|null,
     *   unsubscribedAt: string|null,
     *   scheduledAt: string|null,
     *   cancelledAt: string|null,
     *   createdAt: string,
     *   updatedAt: string
     * }
     */
    public function get(string $emailId): array
    {
        return $this->client->get("/emails/{$emailId}");
    }
}
