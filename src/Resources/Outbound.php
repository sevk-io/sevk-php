<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type OutboundQuota array{
 *   projectId: string,
 *   monthlyEmailLimit: int|null,
 *   adminMonthlyEmailLimit: int|null,
 *   effectiveMonthlyEmailLimit: int|null,
 *   monthlyEmailsUsed: int,
 *   limitEmailsUsed: int,
 *   monthlyRemaining: int|null,
 *   freeMonthlyEmails: int|null,
 *   freeEmailsUsed: int,
 *   rateLimit: int|null,
 *   emailPrice: float|int,
 *   resetAt: string,
 *   pausedAt: string|null,
 *   pausedReason: string|null,
 *   createdAt: string,
 *   updatedAt: string
 * }
 */
class Outbound extends Resource
{
    /** @return OutboundQuota */
    public function getQuota(): array
    {
        return $this->client->get('/outbound-settings/quota');
    }

    /**
     * @param array{monthlyEmailLimit: int|null} $data
     * @return OutboundQuota
     */
    public function updateQuota(array $data): array
    {
        return $this->client->put('/outbound-settings/quota', $data);
    }

    /** @return OutboundQuota */
    public function resetQuota(): array
    {
        return $this->client->post('/outbound-settings/quota/reset');
    }

    /**
     * Reset custom-limit usage while preserving the cap and monthly usage.
     *
     * @return OutboundQuota
     */
    public function resetLimit(): array
    {
        return $this->client->post('/outbound-settings/quota/reset-limit');
    }

    /** @return OutboundQuota */
    public function pauseQuota(): array
    {
        return $this->client->post('/outbound-settings/quota/pause');
    }
}
