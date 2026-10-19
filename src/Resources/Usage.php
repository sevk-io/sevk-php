<?php

declare(strict_types=1);

namespace Sevk\Resources;

class Usage extends Resource
{
    /**
     * @return array{
     *   id: string,
     *   name: string,
     *   audienceLimit: int|null,
     *   contactLimit: int|null,
     *   broadcastLimit: int|null,
     *   domainLimit: int|null,
     *   webhookLimit: int|null,
     *   storageLimit: string|null,
     *   storageUsed: string,
     *   balance?: string,
     *   outboundEmailPrice?: string,
     *   inboundEmailPrice?: string,
     *   monthlyOutboundEmailLimit: int|null,
     *   outboundRateLimit: int|null,
     *   freeMonthlyOutboundEmails: int|null,
     *   freeMonthlyInboundEmails: int|null,
     *   audiencesCount: int,
     *   contactsCount: int,
     *   broadcastsCount: int,
     *   monthlyBroadcastsUsed: int,
     *   domainsCount: int,
     *   webhooksCount: int,
     *   inboundEmailsCount: int,
     *   inbound: array{
     *     emailsCount: int,
     *     monthlyEmailLimit: int|null,
     *     monthlyEmailsUsed: int,
     *     monthlyRemaining: int|null,
     *     freeEmails: array{current: int, limit: int|null, remaining: int},
     *     resetAt: string|null,
     *     pausedAt: string|null,
     *     pausedReason: string|null
     *   },
     *   outbound: array{
     *     monthlyEmailLimit: int|null,
     *     adminMonthlyEmailLimit: int|null,
     *     effectiveMonthlyEmailLimit: int|null,
     *     monthlyEmailsUsed: int,
     *     limitEmailsUsed: int,
     *     monthlyRemaining: int|null,
     *     rateLimit: int|null,
     *     freeEmails: array{current: int, limit: int|null, remaining: int},
     *     resetAt: string|null,
     *     pausedAt: string|null,
     *     pausedReason: string|null
     *   }
     * }
     */
    public function get(): array
    {
        return $this->client->get('/limits');
    }
}
