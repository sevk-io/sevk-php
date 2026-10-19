<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type Template array{
 *   id: string,
 *   title: string,
 *   content: string,
 *   style: 'HTML'|'MARKUP',
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 */
class Templates extends Resource
{
    /**
     * @return array{
     *   items: list<Template>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(?int $page = null, ?int $limit = null, ?string $search = null, ?string $after = null, ?string $before = null): array
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

        return $this->client->get('/templates', $query ?: null);
    }

    /**
     * @param 'HTML'|'MARKUP'|null $style
     * @return Template
     */
    public function create(string $title, string $content, ?string $style = null): array
    {
        $data = ['title' => $title, 'content' => $content];

        if ($style !== null) {
            $data['style'] = $style;
        }

        return $this->client->post('/templates', $data);
    }

    /** @return Template */
    public function get(string $templateId): array
    {
        return $this->client->get("/templates/{$templateId}");
    }

    /**
     * @param 'HTML'|'MARKUP'|null $style
     * @return Template
     */
    public function update(string $templateId, ?string $title = null, ?string $content = null, ?string $style = null): array
    {
        $data = [];

        if ($title !== null) {
            $data['title'] = $title;
        }
        if ($content !== null) {
            $data['content'] = $content;
        }
        if ($style !== null) {
            $data['style'] = $style;
        }

        return $this->client->put("/templates/{$templateId}", $data);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $templateId): array
    {
        return $this->client->delete("/templates/{$templateId}");
    }

    /** @return Template */
    public function duplicate(string $templateId): array
    {
        return $this->client->post("/templates/{$templateId}/duplicate");
    }
}
