<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type Lifecycle 'DRAFT'|'SCHEDULED'|'STARTING'|'SENDING'|'SENT'|'FAILED'|'CANCELLING'|'CANCELLED'
 * @phpstan-type VariantInput array{label: string, subject?: string, body?: string, fromName?: string}
 * @phpstan-type AbTestInput array{
 *   testPercent: int,
 *   evalAfterMinutes: int,
 *   winnerMetric?: 'OPEN_RATE'|'CLICK_RATE'
 * }
 * @phpstan-type Variant array{
 *   id: string,
 *   broadcastId: string,
 *   label: string,
 *   subject: string|null,
 *   body: string|null,
 *   fromName: string|null,
 *   totalContacts: int,
 *   sentCount: int,
 *   failedCount: int,
 *   deliveredCount: int,
 *   openedCount: int,
 *   clickedCount: int,
 *   bouncedCount: int,
 *   complainedCount: int,
 *   unsubscribedCount: int,
 *   isWinner: bool,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type AbTest array{
 *   id: string,
 *   broadcastId: string,
 *   testPercent: int,
 *   evalAfterMinutes: int,
 *   scheduledEvalAt: string|null,
 *   winnerMetric: 'OPEN_RATE'|'CLICK_RATE',
 *   winnerVariantId: string|null,
 *   evaluatedAt: string|null,
 *   status: 'SCHEDULED'|'TESTING'|'EVALUATING'|'ROLLING_OUT'|'COMPLETED'|'FAILED',
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type BroadcastBase array{
 *   id: string,
 *   name: string,
 *   subject: string,
 *   body: string,
 *   style: 'MARKUP'|'HTML',
 *   status: Lifecycle,
 *   targetType: 'ALL'|'AUDIENCE'|'TOPIC'|'SEGMENT',
 *   audienceId: string|null,
 *   topicId: string|null,
 *   segmentId: string|null,
 *   email: string|null,
 *   senderName: string|null,
 *   replyTo: string|null,
 *   replyToInbound: bool,
 *   domainId: string|null,
 *   scheduledAt: string|null,
 *   sentAt: string|null,
 *   completedAt: string|null,
 *   delivered: string|null,
 *   includeListUnsubscribeHeader: bool,
 *   openTracking: bool|null,
 *   clickTracking: bool|null,
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string,
 *   audience: array{
 *     id: string,
 *     name: string,
 *     contactsCount: int,
 *     subscribedContactsCount: int,
 *     broadcastsCount: int,
 *     topicsCount: int,
 *     segmentsCount: int
 *   }|null,
 *   topic: array{
 *     id: string,
 *     name: string,
 *     contactsCount: int,
 *     subscribedContactsCount: int,
 *     broadcastsCount: int
 *   }|null,
 *   segment: array{id: string, name: string}|null,
 *   domain: array{id: string, domain: string, senderName: string|null, verified: bool}|null,
 *   ...
 * }
 * @phpstan-type BroadcastSummary BroadcastBase&array{
 *   estimatedCost: string|null,
 *   actualCost: string|null,
 *   totalContacts?: int,
 *   sentCount?: int,
 *   failedCount?: int,
 *   deliveredCount?: int,
 *   openedCount?: int,
 *   clickedCount?: int,
 *   bouncedCount?: int,
 *   complainedCount?: int,
 *   unsubscribedCount?: int,
 *   ...
 * }
 * @phpstan-type Broadcast BroadcastSummary&array{variants: list<Variant>, abTest: AbTest|null, ...}
 * @phpstan-type BroadcastWithVariants BroadcastBase&array{variants: list<Variant>, abTest: AbTest|null, ...}
 * @phpstan-type BroadcastUpdate BroadcastBase&array{variants?: list<Variant>, abTest?: AbTest|null, ...}
 */
class Broadcasts extends Resource
{
    /**
     * @param Lifecycle|null $status
     * @return array{
     *   items: list<BroadcastSummary>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(?int $page = null, ?int $limit = null, ?string $search = null, ?string $after = null, ?string $before = null, ?string $status = null): array
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
        if ($status !== null) {
            $query['status'] = $status;
        }

        return $this->client->get('/broadcasts', $query ?: null);
    }

    /** @return Broadcast */
    public function get(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}");
    }

    /**
     * @param array{
     *   name: string,
     *   subject: string,
     *   body?: string,
     *   style?: 'MARKUP'|'HTML',
     *   targetType?: 'ALL'|'AUDIENCE'|'TOPIC'|'SEGMENT',
     *   audienceId?: string,
     *   topicId?: string,
     *   segmentId?: string,
     *   senderName: string,
     *   email?: string,
     *   replyTo?: string,
     *   replyToInbound?: bool,
     *   domainId: string,
     *   scheduledAt?: string,
     *   includeListUnsubscribeHeader?: bool,
     *   openTracking?: bool|null,
     *   clickTracking?: bool|null,
     *   variants?: list<VariantInput>,
     *   abTest?: AbTestInput
     * } $params
     * @return BroadcastWithVariants
     */
    public function create(array $params): array
    {
        return $this->client->post('/broadcasts', $params);
    }

    /**
     * @param array{
     *   name?: string,
     *   subject?: string,
     *   body?: string,
     *   style?: 'MARKUP'|'HTML',
     *   targetType?: 'ALL'|'AUDIENCE'|'TOPIC'|'SEGMENT',
     *   audienceId?: string|null,
     *   topicId?: string|null,
     *   segmentId?: string|null,
     *   senderName?: string,
     *   email?: string,
     *   replyTo?: string|null,
     *   replyToInbound?: bool,
     *   domainId?: string,
     *   scheduledAt?: string|null,
     *   includeListUnsubscribeHeader?: bool,
     *   openTracking?: bool|null,
     *   clickTracking?: bool|null,
     *   variants?: list<VariantInput>|null,
     *   abTest?: AbTestInput|null
     * } $params
     * @return BroadcastUpdate Experiment fields are present when this update touched them.
     */
    public function update(string $broadcastId, array $params): array
    {
        return $this->client->put("/broadcasts/{$broadcastId}", $params);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $broadcastId): array
    {
        return $this->client->delete("/broadcasts/{$broadcastId}");
    }

    /** @return BroadcastSummary */
    public function send(string $broadcastId): array
    {
        return $this->client->post("/broadcasts/{$broadcastId}/send");
    }

    /** @return array{broadcast: BroadcastSummary, cancelledJobs: int} */
    public function cancel(string $broadcastId): array
    {
        return $this->client->post("/broadcasts/{$broadcastId}/cancel");
    }

    /**
     * @param array{emails: list<string>} $params
     * @param array{idempotency_key?: string}|null $options
     * @return array{testEmails: int, message: string}
     */
    public function test(string $broadcastId, array $params, ?array $options = null): array
    {
        $headers = $options !== null && array_key_exists('idempotency_key', $options)
            ? ['Idempotency-Key' => $options['idempotency_key']]
            : null;

        return $this->client->post("/broadcasts/{$broadcastId}/test", $params, $headers);
    }

    /**
     * @return array{
     *   totalEmails: int,
     *   sentEmails: int,
     *   deliveredEmails: int,
     *   openedEmails: int,
     *   clickedEmails: int,
     *   totalClicks: int,
     *   bouncedEmails: int,
     *   failedEmails: int,
     *   unsubscribedEmails: int,
     *   deliveryRate: float|int,
     *   openRate: float|int,
     *   clickRate: float|int,
     *   bounceRate: float|int,
     *   unsubscribeRate: float|int
     * }
     */
    public function analytics(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}/analytics");
    }

    /**
     * @return array{
     *   id: string,
     *   name: string,
     *   status: Lifecycle,
     *   totalContacts: int,
     *   emailsSent: int,
     *   failedEmails: int,
     *   pendingEmails: int,
     *   createdAt: string,
     *   sentAt: string|null,
     *   completedAt: string|null,
     *   scheduledAt: string|null,
     *   sendingStartedAt?: string,
     *   step?: string,
     *   stepLabel?: string,
     *   stepIndex?: int,
     *   stepTotal?: int,
     *   stepProgress?: float|int,
     *   warning?: array{type: 'FAILED_EMAILS', message: string, failedCount: int},
     *   progress?: array{
     *     percentage: float|int,
     *     emailsSent: int,
     *     totalContacts: int,
     *     pendingEmails: int,
     *     estimatedTimeRemaining: string|null
     *   },
     *   schedule?: array{scheduledAt: string|null, timeUntilSend: int, willSendIn: string, isPastDue: bool},
     *   completion?: array{completedAt: string|null, totalEmailsSent: int, successRate: float|int}
     * }
     */
    public function status(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}/status");
    }

    /**
     * @param array{page?: int, limit?: int, after?: string, before?: string} $params
     * @return array{
     *   items: list<array{
     *     id: string,
     *     messageId: string|null,
     *     to: string,
     *     subject: string,
     *     status: 'PENDING'|'SENT'|'DELIVERED'|'BOUNCED'|'OPENED'|'COMPLAINT'|'FAILED'|'SUPPRESSED',
     *     type: 'MARKETING'|'TRANSACTIONAL',
     *     openedAt: string|null,
     *     clickedAt: string|null,
     *     unsubscribedAt: string|null,
     *     createdAt: string,
     *     contact: array{id: string|null, email: string}
     *   }>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function emails(string $broadcastId, array $params = []): array
    {
        $query = array_intersect_key($params, array_flip(['page', 'limit', 'after', 'before']));

        return $this->client->get("/broadcasts/{$broadcastId}/emails", $query ?: null);
    }

    /**
     * @return array{
     *   estimatedCost: float|int,
     *   emailPrice: float|int,
     *   contactCount: int,
     *   currentBalance: float|int,
     *   hasSufficientBalance: bool,
     *   shortfall: float|int,
     *   freeEmailsAvailable: int,
     *   freeEmailsToUse: int,
     *   paidEmailsCount: int
     * }
     */
    public function estimateCost(string $broadcastId): array
    {
        return $this->client->get("/broadcasts/{$broadcastId}/estimate-cost");
    }

    /**
     * @param array{
     *   targetType: 'ALL'|'AUDIENCE'|'TOPIC'|'SEGMENT',
     *   audienceId?: string,
     *   topicId?: string,
     *   segmentId?: string
     * } $params
     * @return array{count: int}
     */
    public function estimateRecipients(array $params): array
    {
        return $this->client->post('/broadcasts/estimate-recipients', $params);
    }

    /**
     * @return array{
     *   items: list<array{
     *     id: string,
     *     name: string,
     *     status: Lifecycle,
     *     sentCount: int,
     *     failedCount: int,
     *     totalContacts: int,
     *     scheduledAt: string|null,
     *     abTest: array{
     *       status: 'SCHEDULED'|'TESTING'|'EVALUATING'|'ROLLING_OUT'|'COMPLETED'|'FAILED',
     *       scheduledEvalAt: string|null,
     *       testPercent: int
     *     }|null
     *   }>,
     *   total: int
     * }
     */
    public function active(): array
    {
        return $this->client->get('/broadcasts/active');
    }
}
