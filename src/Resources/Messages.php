<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Media;
use Goomzo\Page;
use Goomzo\Transport;

/**
 * Messages, each to one person, by SMS, WhatsApp or email.
 *
 * @see https://docs.goomzo.africa/docs/developers/php-sdk/messages
 */
final class Messages extends Resource
{
    /**
     * Sends one message, and returns it.
     *
     *     $goomzo->messages->sendOne(['to' => '+255712345678', 'channel' => 'sms', 'content' => ['text' => 'Hi']]);
     *
     *     // A saved template. {{secret.code}} is delivered, but kept as ••••••.
     *     $goomzo->messages->sendOne(['to' => '+255712345678', 'template' => 'auth_1', 'variables' => ['code' => '482913']]);
     *
     * @param array<string, mixed> $message `to` or `customerId`, and `content` or a `template`.
     * @return array<string, mixed>
     */
    public function sendOne(array $message, ?string $idempotencyKey = null): array
    {
        return $this->send(['messages' => [$message]], $idempotencyKey)[0];
    }

    /**
     * Sends up to 100 messages, each to its own person, and returns them in order. They're queued all or none: if one
     * isn't valid, none is sent, and the error names it, such as `messages[1].to`.
     *
     * @param array{messages: list<array<string, mixed>>} $body
     * @return list<array<string, mixed>>
     */
    public function send(array $body, ?string $idempotencyKey = null): array
    {
        return $this->items($this->transport->request('POST', '/v1/messages', body: $body, idempotencyKey: $idempotencyKey));
    }

    /**
     * Gets a message by its id.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->object($this->transport->request('GET', Transport::path('/v1/messages/{id}', ['id' => $id])));
    }

    /**
     * Lists messages, newest first. Loop over the page for every message.
     *
     *     foreach ($goomzo->messages->list(['status' => 'failed']) as $message) {
     *         echo $message['error'];
     *     }
     *
     * @param array{q?: string, customer?: string, channel?: string, status?: string, limit?: int} $query
     */
    public function list(array $query = []): Page
    {
        return $this->page('/v1/messages', $query);
    }

    /**
     * Cancels a scheduled message before it goes. A message already sending can't be canceled.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        return $this->object($this->transport->request('POST', Transport::path('/v1/messages/{id}/cancel', ['id' => $id])));
    }

    /**
     * The sender IDs the business can send from, to use as `from`, by channel.
     *
     * @return array<string, mixed>
     */
    public function senders(): array
    {
        return $this->object($this->transport->request('GET', '/v1/messaging/senders'));
    }

    /**
     * Gets a file a customer sent on WhatsApp, such as a photo or voice note, by the received message's id. Throws
     * ApiException with `media_not_ready` while Goomzo is still fetching it.
     */
    public function media(string $receivedId): Media
    {
        $media = $this->transport->request('GET', Transport::path('/v1/messages/received/{id}/media', ['id' => $receivedId]), file: true);
        if (!$media instanceof Media) {
            throw new \UnexpectedValueException('Goomzo answered without a file.');
        }

        return $media;
    }
}
