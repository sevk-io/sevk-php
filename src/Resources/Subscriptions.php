<?php

declare(strict_types=1);

namespace Sevk\Resources;

class Subscriptions extends Resource
{
    /**
     * @param array<string, mixed>|null $data
     * @return array{
     *   contact: array{id: string, email: string, subscribed: bool, data?: array<string, mixed>|null},
     *   audience?: array{id: string, name: string}|null,
     *   alreadySubscribed?: bool,
     *   message?: string
     * }
     */
    public function subscribe(string $email, ?string $audienceId = null, ?array $data = null): array
    {
        $body = ['email' => $email];

        if ($audienceId !== null) {
            $body['audienceId'] = $audienceId;
        }
        if ($data !== null) {
            $body['data'] = (object) $data;
        }

        return $this->client->post('/subscriptions/subscribe', $body);
    }

    /**
     * @return array{
     *   contact: array{id: string, email: string, subscribed: bool},
     *   alreadyUnsubscribed?: bool
     * }
     */
    public function unsubscribe(?string $email = null, ?string $contactId = null, ?string $broadcastId = null): array
    {
        $body = [];

        if ($email !== null) {
            $body['email'] = $email;
        }
        if ($contactId !== null) {
            $body['contactId'] = $contactId;
        }
        if ($broadcastId !== null) {
            $body['broadcastId'] = $broadcastId;
        }

        return $this->client->post('/subscriptions/unsubscribe', $body);
    }
}
