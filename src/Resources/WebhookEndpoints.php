<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Transport;

/**
 * Webhook endpoints: URLs that hear events as they happen. Check each event with `Webhook::verify()`.
 *
 * @see https://docs.goomzo.africa/docs/developers/php-sdk/webhooks
 */
final class WebhookEndpoints extends Resource
{
    /**
     * Adds an endpoint. It comes with its signing `secret`: keep it, to check events with `Webhook::verify()`.
     *
     *     $goomzo->webhookEndpoints->create(['url' => 'https://example.com/goomzo', 'events' => ['message.delivered']]);
     *
     * @param array{url: string, events: list<string>, description?: string} $endpoint
     * @return array<string, mixed>
     */
    public function create(array $endpoint): array
    {
        return $this->object($this->transport->request('POST', '/v1/webhook-endpoints', body: $endpoint));
    }

    /**
     * Gets an endpoint by its id.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->object($this->transport->request('GET', Transport::path('/v1/webhook-endpoints/{id}', ['id' => $id])));
    }

    /**
     * Lists the endpoints.
     *
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->items($this->transport->request('GET', '/v1/webhook-endpoints'));
    }

    /**
     * Changes an endpoint: its URL, its events, or whether it's on.
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    public function update(string $id, array $changes): array
    {
        $route = Transport::path('/v1/webhook-endpoints/{id}', ['id' => $id]);

        return $this->object($this->transport->request('PATCH', $route, body: $changes));
    }

    /**
     * Deletes an endpoint.
     */
    public function delete(string $id): void
    {
        $this->transport->request('DELETE', Transport::path('/v1/webhook-endpoints/{id}', ['id' => $id]));
    }
}
