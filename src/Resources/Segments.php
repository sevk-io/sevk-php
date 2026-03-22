<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Sevk;

class Segments
{
    private Sevk $client;

    public function __construct(Sevk $client)
    {
        $this->client = $client;
    }

    public function list(string $audienceId, ?int $page = null, ?int $limit = null): array
    {
        $query = [];
        if ($page !== null) $query['page'] = $page;
        if ($limit !== null) $query['limit'] = $limit;

        return $this->client->get("/audiences/{$audienceId}/segments", $query ?: null);
    }

    public function create(string $audienceId, string $name, array $rules, string $operator = 'AND'): array
    {
        return $this->client->post("/audiences/{$audienceId}/segments", [
            'name' => $name,
            'rules' => $rules,
            'operator' => $operator,
        ]);
    }

    public function get(string $audienceId, string $segmentId): array
    {
        return $this->client->get("/audiences/{$audienceId}/segments/{$segmentId}");
    }

    public function update(string $audienceId, string $segmentId, ?string $name = null, ?array $rules = null, ?string $operator = null): array
    {
        $data = [];
        if ($name !== null) $data['name'] = $name;
        if ($rules !== null) $data['rules'] = $rules;
        if ($operator !== null) $data['operator'] = $operator;

        return $this->client->put("/audiences/{$audienceId}/segments/{$segmentId}", $data);
    }

    public function delete(string $audienceId, string $segmentId): array
    {
        return $this->client->delete("/audiences/{$audienceId}/segments/{$segmentId}");
    }

    public function calculate(string $audienceId, string $segmentId): array
    {
        return $this->client->get("/audiences/{$audienceId}/segments/{$segmentId}/calculate");
    }

    public function preview(string $audienceId, array $data): array
    {
        return $this->client->post("/audiences/{$audienceId}/segments/preview", $data);
    }
}
