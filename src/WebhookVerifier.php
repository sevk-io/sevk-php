<?php

declare(strict_types=1);

namespace Sevk;

/**
 * Verifier for sevk webhook deliveries.
 *
 * sevk uses the Svix-compatible signature scheme (svix-id, svix-timestamp,
 * svix-signature headers), so this helper or the official svix-php package
 * can be used. sevk issues canonical whsec_ base64 secrets that can be passed directly to either verifier.
 */
class WebhookVerifier
{
    private const TOLERANCE_SECONDS = 5 * 60;
    private const CANONICAL_BASE64 = '/^(?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?$/D';

    private string $key;

    /** Prepares a verifier with the signing secret issued for a webhook. */
    public function __construct(string $secret)
    {
        if ($secret === '') {
            throw new WebhookVerificationException('Webhook secret is required');
        }

        if (str_starts_with($secret, 'whsec_')) {
            $encoded = substr($secret, 6);
            $decoded = preg_match(self::CANONICAL_BASE64, $encoded) === 1 ? base64_decode($encoded, true) : false;

            if ($decoded === false || $decoded === '') {
                throw new WebhookVerificationException('Invalid base64 secret');
            }
            $this->key = $decoded;
        } else {
            $this->key = $secret;
        }
    }

    /**
     * Verifies the delivery signature and timestamp before decoding its JSON payload.
     *
     * @param array<string,string|null> $headers
     * @return mixed Parsed JSON payload
     */
    public function verify(string $payload, array $headers): mixed
    {
        $msgId = $this->header($headers, 'svix-id');
        $msgTimestamp = $this->header($headers, 'svix-timestamp');
        $msgSignature = $this->header($headers, 'svix-signature');

        if ($msgId === null || $msgId === '' || $msgTimestamp === null || $msgSignature === null) {
            throw new WebhookVerificationException('Missing required svix headers');
        }

        if (preg_match('/^\d+$/D', $msgTimestamp) !== 1) {
            throw new WebhookVerificationException('Invalid timestamp header');
        }
        $timestamp = (int) $msgTimestamp;
        $now = time();

        if ($timestamp < $now - self::TOLERANCE_SECONDS || $timestamp > $now + self::TOLERANCE_SECONDS) {
            throw new WebhookVerificationException('Timestamp outside tolerance window');
        }

        $toSign = $msgId . '.' . $msgTimestamp . '.' . $payload;
        $expected = 'v1,' . base64_encode(hash_hmac('sha256', $toSign, $this->key, true));

        foreach (explode(' ', $msgSignature) as $candidate) {
            if (hash_equals($expected, $candidate)) {
                try {
                    return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $error) {
                    throw new WebhookVerificationException('Invalid JSON payload', 0, $error);
                }
            }
        }

        throw new WebhookVerificationException('Signature mismatch');
    }

    /**
     * @param array<string,string|null> $headers
     */
    private function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            $normalized = strtolower(str_replace('_', '-', $key));
            if (str_starts_with($normalized, 'http-')) {
                $normalized = substr($normalized, 5);
            }
            if ($normalized === $name && $value !== null) {
                return $value;
            }
        }

        return null;
    }
}
