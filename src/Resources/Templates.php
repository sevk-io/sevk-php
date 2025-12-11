<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Templates
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function list(?int $page = null, ?int $limit = null, ?string $search = null): array
    {
        $query = [];
        if ($page !== null) $query['page'] = $page;
        if ($limit !== null) $query['limit'] = $limit;
        if ($search !== null) $query['search'] = $search;

        return $this->client->get('/templates', $query ?: null);
    }

    public function create(string $title, string $content): array
    {
        return $this->client->post('/templates', [
            'title' => $title,
            'content' => $content,
        ]);
    }

    public function get(string $templateId): array
    {
        return $this->client->get("/templates/{$templateId}");
    }

    public function update(string $templateId, ?string $title = null, ?string $content = null): array
    {
        $data = [];
        if ($title !== null) $data['title'] = $title;
        if ($content !== null) $data['content'] = $content;

        return $this->client->put("/templates/{$templateId}", $data);
    }

    public function delete(string $templateId): array
    {
        return $this->client->delete("/templates/{$templateId}");
    }

    public function duplicate(string $templateId): array
    {
        return $this->client->post("/templates/{$templateId}/duplicate");
    }
}
