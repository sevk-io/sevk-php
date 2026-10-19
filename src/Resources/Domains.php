<?php

declare(strict_types=1);

namespace Sevk\Resources;

/**
 * @phpstan-type DnsRecord array{type: 'CNAME'|'TXT'|'MX', name: string, fqdn: string, value: string}
 * @phpstan-type DomainBase array{
 *   id: string,
 *   domain: string,
 *   email: string,
 *   senderName: string|null,
 *   region: string,
 *   verified: bool,
 *   clickTracking: bool,
 *   openTracking: bool,
 *   tlsPolicy: 'OPTIONAL'|'REQUIRE',
 *   inboundEnabled: bool,
 *   inboundVerified: bool,
 *   inboundPrefix: string,
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 * @phpstan-type DomainRead array{
 *   id: string,
 *   domain: string,
 *   email: string,
 *   senderName: string|null,
 *   region: string,
 *   verified: bool,
 *   verificationToken?: string,
 *   clickTracking: bool,
 *   openTracking: bool,
 *   tlsPolicy: 'OPTIONAL'|'REQUIRE',
 *   inboundEnabled: bool,
 *   inboundVerified: bool,
 *   inboundPrefix: string,
 *   dkimTokens?: list<string>,
 *   dnsRecords?: list<DnsRecord>,
 *   projectId: string,
 *   createdAt: string,
 *   updatedAt: string
 * }
 */
class Domains extends Resource
{
    /**
     * @return array{
     *   items: list<DomainBase>,
     *   total?: int,
     *   page?: int,
     *   totalPages?: int,
     *   hasMore?: bool,
     *   nextCursor?: string|null,
     *   prevCursor?: string|null
     * }
     */
    public function list(?bool $verified = null, ?int $page = null, ?int $limit = null, ?string $after = null, ?string $before = null): array
    {
        $query = [];

        if ($verified !== null) {
            $query['verified'] = $verified ? 'true' : 'false';
        }
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

        return $this->client->get('/domains', $query ?: null);
    }

    /** @return DomainRead */
    public function get(string $domainId): array
    {
        return $this->client->get("/domains/{$domainId}");
    }

    /**
     * @param array{domain: string, email: string, senderName?: string, region?: string} $params
     * @return DomainRead
     */
    public function create(array $params): array
    {
        return $this->client->post('/domains', $params);
    }

    /**
     * @param array{
     *   email?: string,
     *   senderName?: string,
     *   clickTracking?: bool,
     *   openTracking?: bool,
     *   tlsPolicy?: 'OPTIONAL'|'REQUIRE',
     *   inboundEnabled?: bool,
     *   inboundPrefix?: string
     * } $params
     * @return DomainRead
     */
    public function update(string $domainId, array $params): array
    {
        return $this->client->put("/domains/{$domainId}", $params);
    }

    /** @return array{id: string, deleted: true} */
    public function delete(string $domainId): array
    {
        return $this->client->delete("/domains/{$domainId}");
    }

    /** @return DomainRead */
    public function verify(string $domainId): array
    {
        return $this->client->post("/domains/{$domainId}/verify");
    }

    /**
     * @return array{
     *   id: string,
     *   domain: string,
     *   email: string,
     *   senderName: string|null,
     *   region: string,
     *   verified: bool,
     *   verificationToken?: string,
     *   clickTracking: bool,
     *   openTracking: bool,
     *   tlsPolicy: 'OPTIONAL'|'REQUIRE',
     *   inboundEnabled: bool,
     *   inboundVerified: bool,
     *   inboundPrefix: string,
     *   dkimTokens?: list<string>,
     *   dnsRecords?: list<DnsRecord>,
     *   projectId: string,
     *   createdAt: string,
     *   updatedAt: string,
     *   inboundMxResolved: list<array{exchange: string, priority: int}>,
     *   inboundExpectedTarget: string
     * }
     */
    public function verifyInbound(string $domainId): array
    {
        return $this->client->post("/domains/{$domainId}/inbound/verify");
    }

    /** @return array{items: list<DnsRecord>} */
    public function dnsRecords(string $domainId): array
    {
        return $this->client->get("/domains/{$domainId}/dns-records");
    }

    /** @return array{items: list<string>} */
    public function regions(): array
    {
        return $this->client->get('/domains/regions');
    }
}
