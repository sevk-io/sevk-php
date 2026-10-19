<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type Rule array{
 *   field: string,
 *   operator: 'equals'|'notEquals'|'contains'|'notContains'|'startsWith'|'endsWith'|'greaterThan'|'lessThan'|'greaterOrEqual'|'lessOrEqual'|'isEmpty'|'isNotEmpty'|'in'|'notIn',
 *   value: mixed,
 *   groupId?: string,
 *   groupOperator?: 'AND'|'OR'
 * }
 * @phpstan-type Group array{id: string, operator: 'AND'|'OR'}
 * @phpstan-type Segment array{
 *   id: string,
 *   name: string,
 *   rules: list<Rule>,
 *   operator: 'AND'|'OR',
 *   limitToTopics: list<string>,
 *   groups: list<Group>|null,
 *   audienceId: string,
 *   createdAt: string,
 *   updatedAt: string,
 *   lastRunAt: string|null,
 *   lastRunCount: int|null
 * }
 */
class Segments extends Resource
{
    /**
     * @return array{
     *   items: list<Segment>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(string $audienceId, ?int $page = null, ?int $limit = null, ?string $search = null, ?string $after = null, ?string $before = null): array
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

        return $this->client->get("/audiences/{$audienceId}/segments", $query ?: null);
    }

    /**
     * @param list<Rule> $rules
     * @param 'AND'|'OR' $operator
     * @param list<string>|null $limitToTopics
     * @param list<Group>|null $groups
     * @return Segment
     */
    public function create(
        string $audienceId,
        string $name,
        array $rules,
        string $operator = 'AND',
        ?array $limitToTopics = null,
        ?array $groups = null
    ): array {
        $body = [
            'name' => $name,
            'rules' => $rules,
            'operator' => $operator,
        ];

        if ($limitToTopics !== null) {
            $body['limitToTopics'] = $limitToTopics;
        }
        if ($groups !== null) {
            $body['groups'] = $groups;
        }

        return $this->client->post("/audiences/{$audienceId}/segments", $body);
    }

    /**
     * @param array{
     *   rules: list<Rule>,
     *   operator: 'AND'|'OR',
     *   limitToTopics?: list<string>,
     *   groups?: list<Group>
     * } $data
     * @return array{
     *   count: int,
     *   contacts: list<array{
     *     id: string,
     *     email: string,
     *     subscribed: bool,
     *     data: array<string, mixed>|null,
     *     createdAt: string
     *   }>
     * }
     */
    public function preview(string $audienceId, array $data): array
    {
        return $this->client->post("/audiences/{$audienceId}/segments/preview", $data);
    }

    /** @return Segment */
    public function get(string $segmentId): array
    {
        return $this->client->get("/segments/{$segmentId}");
    }

    /**
     * @param list<Rule>|null $rules
     * @param 'AND'|'OR'|null $operator
     * @param list<string>|null $limitToTopics
     * @param list<Group>|null $groups
     * @return Segment
     */
    public function update(
        string $segmentId,
        ?string $name = null,
        ?array $rules = null,
        ?string $operator = null,
        ?array $limitToTopics = null,
        ?array $groups = null
    ): array {
        $data = [];

        if ($name !== null) {
            $data['name'] = $name;
        }
        if ($rules !== null) {
            $data['rules'] = $rules;
        }
        if ($operator !== null) {
            $data['operator'] = $operator;
        }
        if ($limitToTopics !== null) {
            $data['limitToTopics'] = $limitToTopics;
        }
        if ($groups !== null) {
            $data['groups'] = $groups;
        }

        return $this->client->put("/segments/{$segmentId}", $data);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $segmentId): array
    {
        return $this->client->delete("/segments/{$segmentId}");
    }

    /** @return array{count: int, segmentId: string, segmentName: string, lastRunAt: string} */
    public function calculate(string $segmentId): array
    {
        return $this->client->get("/segments/{$segmentId}/calculate");
    }
}
