<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Emails
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    /**
     * Send an email with optional attachments
     *
     * @param array $params Email parameters
     *   - from: string (optional)
     *   - to: string|array
     *   - subject: string
     *   - html: string (optional)
     *   - text: string (optional)
     *   - attachments: array (optional) - Array of attachment objects (max 10, 10MB total)
     *       Each attachment: ['filename' => string, 'content' => string (base64), 'contentType' => string]
     * @return array Response with 'id' or 'ids'
     */
    public function send(array $params): array
    {
        return $this->client->post('/emails', $params);
    }

    /**
     * Send multiple emails in bulk
     *
     * @param array $emails Array of email parameter arrays (max 100)
     * @return array Response with success/failed counts
     *   - success: int
     *   - failed: int
     *   - ids: array
     *   - errors: array (optional)
     */
    public function sendBulk(array $emails): array
    {
        return $this->client->post('/emails/bulk', ['emails' => $emails]);
    }

    public function get(string $emailId): array
    {
        return $this->client->get("/emails/{$emailId}");
    }
}
