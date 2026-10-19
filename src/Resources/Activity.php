<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type ActivityItem array{
 *   id: string,
 *   eventType: 'EMAIL'|'SUBSCRIPTION',
 *   action: 'REQUESTED'|'SENT'|'DELIVERED'|'OPENED'|'CLICKED'|'BOUNCED'|'COMPLAINT'|'FAILED'|'SUBSCRIBED'|'UNSUBSCRIBED'|'DELAYED'|'REJECTED',
 *   description: string|null,
 *   emailId: string|null,
 *   emailSubject: string|null,
 *   contactId: string|null,
 *   contactEmail: string|null,
 *   broadcastId: string|null,
 *   broadcastName: string|null,
 *   link: string|null,
 *   userAgent: string|null,
 *   metadata: string|null,
 *   createdAt: string
 * }
 */
class Activity extends Resource
{
    /**
     * @param array{
     *   page?: int,
     *   limit?: int,
     *   after?: string,
     *   before?: string,
     *   category?: 'EMAIL'|'SUBSCRIPTION'|'all',
     *   type?: string|list<string>,
     *   days?: int,
     *   search?: string
     * } $params
     * @return array{
     *   items: list<ActivityItem>,
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
        if (isset($params['type']) && is_array($params['type'])) {
            $params['type'] = implode(',', $params['type']);
        }

        return $this->client->get('/activity', $params ?: null);
    }

    /**
     * @return array{
     *   totalEmails: int,
     *   sentEmails: int,
     *   deliveredEmails: int,
     *   openedEmails: int,
     *   clickedEmails: int,
     *   bouncedEmails: int,
     *   marketingEmails: int,
     *   transactionalEmails: int,
     *   rates: array{open: float|int, click: float|int, bounce: float|int, delivery: float|int}
     * }
     */
    public function stats(): array
    {
        return $this->client->get('/activity/stats');
    }
}
