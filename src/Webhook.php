<?php

declare(strict_types=1);

namespace Goomzo;

use Goomzo\Exception\WebhookVerificationException;

/**
 * Checks webhook deliveries.
 */
final class Webhook
{
    private const TOLERANCE = 5 * 60;

    /**
     * Checks that a webhook delivery came from Goomzo and is recent, then returns its event.
     *
     * Pass the body exactly as it arrived, before decoding it: the signature is of those bytes. Pass the request's
     * headers, as `['Webhook-Id' => '…']` or as a PSR-7 request's `getHeaders()`, and the endpoint's signing secret,
     * `whsec_…`.
     *
     *     $event = Webhook::verify(file_get_contents('php://input'), getallheaders(), getenv('GOOMZO_WEBHOOK_SECRET'));
     *     if ($event['type'] === 'message.delivered') { … }
     *
     * @param array<string, string|list<string>> $headers
     * @return array{id: string, type: string, timestamp: string, organizationId: string, data: array<string, mixed>}
     *
     * @throws WebhookVerificationException when the signature doesn't match, or the delivery is more than 5 minutes old.
     */
    public static function verify(string $payload, array $headers, string $secret, ?int $now = null): array
    {
        $id = self::header($headers, 'webhook-id');
        $timestamp = self::header($headers, 'webhook-timestamp');
        $signatures = self::header($headers, 'webhook-signature');
        if ($id === null || $timestamp === null || $signatures === null) {
            throw new WebhookVerificationException('The webhook-id, webhook-timestamp and webhook-signature headers are needed.');
        }
        if (!ctype_digit($timestamp) || abs(($now ?? time()) - (int) $timestamp) > self::TOLERANCE) {
            throw new WebhookVerificationException('The delivery is too old, or from the future: check your clock.');
        }

        $expected = base64_encode(hash_hmac('sha256', "{$id}.{$timestamp}.{$payload}", self::key($secret), true));
        // While a secret is being rotated, a delivery carries a signature for each: any of them will do.
        $matched = false;
        foreach (explode(' ', $signatures) as $part) {
            [$version, $signature] = array_pad(explode(',', $part, 2), 2, '');
            if ($version === 'v1' && hash_equals($expected, $signature)) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            throw new WebhookVerificationException("The signature doesn't match: check the secret is this endpoint's.");
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new WebhookVerificationException("The body isn't JSON.");
        }

        /** @var array{id: string, type: string, timestamp: string, organizationId: string, data: array<string, mixed>} $event */
        return $event;
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    private static function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                $value = is_array($value) ? ($value[0] ?? null) : $value;

                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    private static function key(string $secret): string
    {
        $raw = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $decoded = base64_decode($raw, true);

        return $decoded === false ? $raw : $decoded;
    }
}
