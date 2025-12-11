<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Subscriptions
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function subscribe(string $email, string $audienceId, ?array $topicIds = null): array
    {
        $data = [
            'email' => $email,
            'audienceId' => $audienceId,
        ];
        if ($topicIds !== null) $data['topicIds'] = $topicIds;

        return $this->client->post('/subscriptions/subscribe', $data);
    }

    public function unsubscribe(string $email, ?string $audienceId = null, ?array $topicIds = null): array
    {
        $data = ['email' => $email];
        if ($audienceId !== null) $data['audienceId'] = $audienceId;
        if ($topicIds !== null) $data['topicIds'] = $topicIds;

        return $this->client->post('/subscriptions/unsubscribe', $data);
    }
}
