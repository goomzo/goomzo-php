<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Page;
use Goomzo\Transport;

/**
 * Broadcasts: one message to segments, a list of numbers, or every customer, filled in for each person.
 *
 * @see https://docs.goomzo.africa/docs/developers/messages/broadcasts
 */
final class Broadcasts extends Resource
{
    /**
     * Sends a broadcast, now or at `sendAt`. `audience` is required: leaving it out is an error, never a send to
     * everyone.
     *
     *     $goomzo->broadcasts->create([
     *         'name' => 'Black Friday',
     *         'channel' => 'sms',
     *         'content' => ['text' => 'Hi {{name|there}}, 20% off today.'],
     *         'audience' => ['segments' => [['name' => 'VIP']]],
     *     ]);
     *
     * @param array<string, mixed> $broadcast
     * @return array<string, mixed>
     */
    public function create(array $broadcast, ?string $idempotencyKey = null): array
    {
        return $this->object($this->transport->request('POST', '/v1/broadcasts', body: $broadcast, idempotencyKey: $idempotencyKey));
    }

    /**
     * Gets a broadcast, with how its messages are doing.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->object($this->transport->request('GET', Transport::path('/v1/broadcasts/{id}', ['id' => $id])));
    }

    /**
     * Lists broadcasts, newest first. Loop over the page for all of them.
     *
     * @param array{q?: string, channel?: string, status?: string, limit?: int} $query
     */
    public function list(array $query = []): Page
    {
        return $this->page('/v1/broadcasts', $query);
    }

    /**
     * Cancels a scheduled broadcast before it goes.
     *
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        return $this->object($this->transport->request('POST', Transport::path('/v1/broadcasts/{id}/cancel', ['id' => $id])));
    }
}
