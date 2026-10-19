<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type InboundStatus 'RECEIVED'|'PROCESSED'|'BOUNCED'|'BLOCKED'|'FAILED'
 * @phpstan-type Attachment array{id: string, filename: string, mimeType: string, contentId: string|null, size: int, createdAt: string}
 * @phpstan-type InboundSummary array{
 *   id: string,
 *   messageId: string|null,
 *   fromAddress: string,
 *   toAddress: string,
 *   subject: string|null,
 *   status: InboundStatus,
 *   isSpam: bool,
 *   isVirus: bool,
 *   spamScore: float|int|null,
 *   rawSize: int,
 *   domainId: string|null,
 *   replyToEmailId: string|null,
 *   replyToBroadcastId: string|null,
 *   receivedAt: string,
 *   createdAt: string,
 *   replyToEmail: array{id: string, subject: string|null, to: string|null}|null,
 *   replyToBroadcast: array{id: string, name: string}|null
 * }
 * @phpstan-type InboundEmail array{
 *   id: string,
 *   messageId?: string|null,
 *   fromAddress: string,
 *   toAddress: string,
 *   ccAddresses?: list<string>,
 *   subject?: string|null,
 *   status: InboundStatus,
 *   isSpam: bool,
 *   isVirus: bool,
 *   spamScore?: float|int|null,
 *   rawSize: int,
 *   domainId: string|null,
 *   receivedAt: string,
 *   createdAt: string,
 *   inReplyTo?: string|null,
 *   references?: list<string>,
 *   bodyText?: string|null,
 *   bodyHtml?: string|null,
 *   headers?: mixed,
 *   projectId?: string,
 *   processedAt?: string|null,
 *   updatedAt?: string,
 *   attachments?: list<Attachment>,
 *   replyToEmailId?: string|null,
 *   replyToBroadcastId?: string|null,
 *   replyToEmail?: array{
 *     id: string,
 *     subject: string|null,
 *     to: string|null,
 *     messageId?: string|null,
 *     from?: string|null,
 *     status?: 'PENDING'|'SCHEDULED'|'SENT'|'DELIVERED'|'BOUNCED'|'OPENED'|'COMPLAINT'|'FAILED'|'SUPPRESSED'|'CANCELLED',
 *     createdAt?: string,
 *     broadcastId?: string|null,
 *     contactId?: string|null
 *   }|null,
 *   replyToBroadcast?: array{
 *     id: string,
 *     name: string,
 *     subject?: string|null,
 *     status?: 'DRAFT'|'SCHEDULED'|'STARTING'|'SENDING'|'SENT'|'FAILED'|'CANCELLING'|'CANCELLED',
 *     createdAt?: string
 *   }|null
 * }
 * @phpstan-type Route array{
 *   id: string,
 *   projectId: string,
 *   name: string,
 *   pattern: string,
 *   target: string,
 *   priority: int,
 *   enabled: bool,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type BlocklistEntry array{
 *   id: string,
 *   projectId: string,
 *   type: 'EMAIL'|'DOMAIN'|'IP',
 *   value: string,
 *   reason?: string|null,
 *   createdAt: string
 * }
 * @phpstan-type InboundQuota array{
 *   projectId: string,
 *   monthlyEmailLimit?: int|null,
 *   monthlyEmailsUsed: int,
 *   freeMonthlyInboundEmails: int|null,
 *   freeEmailsUsed: int,
 *   inboundEmailPrice: float|int,
 *   resetAt: string,
 *   pausedAt?: string|null,
 *   pausedReason?: string|null,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type SecurityConfig array{
 *   id?: string,
 *   domainId: string,
 *   dmarcStrict: bool,
 *   spfRequired: bool,
 *   spamThreshold: float|null,
 *   catchAllEnabled: bool,
 *   acceptedRecipients: string[],
 *   maxAttachmentSize: int,
 *   allowedMimeTypes: list<string>,
 *   createdAt?: string,
 *   updatedAt?: string,
 *   platform: array{maxAttachmentSizeCap: int}
 * }
 */
class Inbound extends Resource
{
    /**
     * @param array{
     *   page?: int,
     *   limit?: int,
     *   after?: string,
     *   before?: string,
     *   search?: string,
     *   status?: InboundStatus,
     *   domainId?: string,
     *   broadcastId?: string,
     *   isSpam?: bool,
     *   isVirus?: bool
     * } $params
     * @return array{
     *   items: list<InboundSummary>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(array $params = []): array
    {
        foreach (['isSpam', 'isVirus'] as $flag) {
            if (is_bool($params[$flag] ?? null)) {
                $params[$flag] = $params[$flag] ? 'true' : 'false';
            }
        }

        return $this->client->get('/inbound', $params ?: null);
    }

    /** @return InboundEmail */
    public function get(string $id): array
    {
        return $this->client->get("/inbound/{$id}");
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $id): array
    {
        return $this->client->delete("/inbound/{$id}");
    }

    /**
     * @return array{
     *   total: int,
     *   counts: array{RECEIVED: int, PROCESSED: int, BOUNCED: int, BLOCKED: int, FAILED: int},
     *   received: int,
     *   processed: int,
     *   bounced: int,
     *   blocked: int,
     *   blocklistRejected: int,
     *   spam: int,
     *   failed: int
     * }
     */
    public function stats(?string $domainId = null): array
    {
        $query = [];

        if ($domainId !== null) {
            $query['domainId'] = $domainId;
        }

        return $this->client->get('/inbound/stats', $query ?: null);
    }

    /** @return array{url: string, filename: string, mimeType: string} */
    public function downloadAttachment(string $emailId, string $attachmentId): array
    {
        return $this->client->get("/inbound/{$emailId}/attachments/{$attachmentId}/download");
    }

    /**
     * @param array{page?: int, limit?: int, after?: string, before?: string} $params
     * @return array{
     *   items: list<Route>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function listRoutes(array $params = []): array
    {
        return $this->client->get('/inbound-settings/routes', $params);
    }

    /**
     * @param array{page?: int, limit?: int, after?: string, before?: string} $params
     * @return list<Route>
     */
    public function routeItems(array $params = []): array
    {
        return $this->listRoutes($params)['items'];
    }

    /** @return Route */
    public function getRoute(string $id): array
    {
        return $this->client->get("/inbound-settings/routes/{$id}");
    }

    /**
     * @param array{name: string, pattern: string, target: string, priority?: int, enabled?: bool} $data
     * @return Route
     */
    public function createRoute(array $data): array
    {
        return $this->client->post('/inbound-settings/routes', $data);
    }

    /**
     * @param array{name?: string, pattern?: string, target?: string, priority?: int, enabled?: bool} $data
     * @return Route
     */
    public function updateRoute(string $id, array $data): array
    {
        return $this->client->put("/inbound-settings/routes/{$id}", $data);
    }

    /** @return array{id: string, deleted: true} */
    public function deleteRoute(string $id): array
    {
        return $this->client->delete("/inbound-settings/routes/{$id}");
    }

    /**
     * @param array{page?: int, limit?: int, after?: string, before?: string} $params
     * @return array{
     *   items: list<BlocklistEntry>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function listBlocklist(array $params = []): array
    {
        return $this->client->get('/inbound-settings/blocklist', $params);
    }

    /**
     * @param array{page?: int, limit?: int, after?: string, before?: string} $params
     * @return list<BlocklistEntry>
     */
    public function blocklistItems(array $params = []): array
    {
        return $this->listBlocklist($params)['items'];
    }

    /**
     * @param array{type: 'EMAIL'|'DOMAIN'|'IP', value: string, reason?: string|null} $data
     * @return BlocklistEntry
     */
    public function addBlocklistEntry(array $data): array
    {
        return $this->client->post('/inbound-settings/blocklist', $data);
    }

    /** @return array{id: string, deleted: true} */
    public function removeBlocklistEntry(string $id): array
    {
        return $this->client->delete("/inbound-settings/blocklist/{$id}");
    }

    /** @return InboundQuota */
    public function getQuota(): array
    {
        return $this->client->get('/inbound-settings/quota');
    }

    /**
     * @param array{monthlyEmailLimit?: int|null} $data
     * @return InboundQuota
     */
    public function updateQuota(array $data): array
    {
        return $this->client->put('/inbound-settings/quota', $data);
    }

    /** @return InboundQuota */
    public function resetQuota(): array
    {
        return $this->client->post('/inbound-settings/quota/reset');
    }

    /** @return SecurityConfig */
    public function getSecurityConfig(string $domainId): array
    {
        return $this->client->get("/inbound-settings/security/{$domainId}");
    }

    /**
     * @param array{
     *   dmarcStrict?: bool,
     *   spfRequired?: bool,
     *   spamThreshold?: float|null,
     *   catchAllEnabled?: bool,
     *   acceptedRecipients?: string[],
     *   maxAttachmentSize?: int,
     *   allowedMimeTypes?: list<string>
     * } $data
     * @return SecurityConfig
     */
    public function updateSecurityConfig(string $domainId, array $data): array
    {
        return $this->client->put("/inbound-settings/security/{$domainId}", $data);
    }
}
