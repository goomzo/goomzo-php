<?php

declare(strict_types=1);

namespace Goomzo\Tests;

use Goomzo\Exception\WebhookVerificationException;
use Goomzo\Webhook;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    private const NOW = 1_794_218_400;

    private string $key;

    private string $secret;

    private string $body;

    protected function setUp(): void
    {
        $this->key = random_bytes(24);
        $this->secret = 'whsec_' . base64_encode($this->key);
        $this->body = json_encode([
            'id' => 'evt_1',
            'type' => 'customer.created',
            'timestamp' => '2026-11-09T10:00:00Z',
            'organizationId' => 'org_1',
            'data' => ['id' => 'cus_1'],
        ], JSON_THROW_ON_ERROR);
    }

    private function sign(string $key, int $timestamp = self::NOW, ?string $body = null): string
    {
        $body ??= $this->body;

        return 'v1,' . base64_encode(hash_hmac('sha256', "evt_1.{$timestamp}.{$body}", $key, true));
    }

    /**
     * @return array<string, string>
     */
    private static function headers(string $signature, int $timestamp = self::NOW): array
    {
        return ['webhook-id' => 'evt_1', 'webhook-timestamp' => (string) $timestamp, 'webhook-signature' => $signature];
    }

    public function testADeliverySignedWithTheSecretGivesItsEvent(): void
    {
        $event = Webhook::verify($this->body, self::headers($this->sign($this->key)), $this->secret, self::NOW);
        self::assertSame('customer.created', $event['type']);
        self::assertSame(['id' => 'cus_1'], $event['data']);
    }

    public function testPsr7HeadersWorkAndAnySignatureWillDo(): void
    {
        $both = $this->sign(random_bytes(24)) . ' ' . $this->sign($this->key);
        $psr7 = ['Webhook-Id' => ['evt_1'], 'Webhook-Timestamp' => [(string) self::NOW], 'Webhook-Signature' => [$both]];
        self::assertSame('evt_1', Webhook::verify($this->body, $psr7, $this->secret, self::NOW)['id']);
    }

    public function testAChangedBodyIsRefused(): void
    {
        $this->expectException(WebhookVerificationException::class);
        Webhook::verify(str_replace('cus_1', 'cus_2', $this->body), self::headers($this->sign($this->key)), $this->secret, self::NOW);
    }

    public function testAnotherSecretIsRefused(): void
    {
        $this->expectException(WebhookVerificationException::class);
        Webhook::verify($this->body, self::headers($this->sign(random_bytes(24))), $this->secret, self::NOW);
    }

    public function testAnOldDeliveryIsRefused(): void
    {
        $old = self::NOW - 600;
        $this->expectExceptionMessage('too old');
        Webhook::verify($this->body, self::headers($this->sign($this->key, $old), $old), $this->secret, self::NOW);
    }

    public function testMissingHeadersAreRefused(): void
    {
        $this->expectExceptionMessage('headers are needed');
        Webhook::verify($this->body, ['webhook-id' => 'evt_1'], $this->secret, self::NOW);
    }

    public function testASecretThatIsntBase64SignsWithItsOwnBytes(): void
    {
        $plain = 'not base64!';
        $event = Webhook::verify($this->body, self::headers($this->sign($plain)), "whsec_{$plain}", self::NOW);
        self::assertSame('evt_1', $event['id']);
    }
}
