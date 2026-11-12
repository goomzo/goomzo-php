<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Page;
use Goomzo\Transport;

/**
 * Customers: the people the business knows, with their segments and activity.
 *
 * @see https://docs.goomzo.africa/docs/developers/php-sdk/customers
 */
final class Customers extends Resource
{
    /** The segments a customer is in. */
    public readonly CustomerSegments $segments;

    /** A customer's activity. */
    public readonly CustomerActivity $activity;

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->segments = new CustomerSegments($transport);
        $this->activity = new CustomerActivity($transport);
    }

    /**
     * Adds up to 100 customers, with their segments and activity, all or none, and returns what became of each:
     * `created`, `updated` or `unchanged`. With `ifExists` `update`, a customer whose phone or email someone already
     * has is updated instead.
     *
     *     $goomzo->customers->create([
     *         'ifExists' => 'update',
     *         'customers' => [['name' => 'Amina Juma', 'phone' => '+255712345678', 'segments' => [['name' => 'Shoppers']]]],
     *     ]);
     *
     * @param array{customers: list<array<string, mixed>>, ifExists?: 'error'|'update', ifSegmentMissing?: 'error'|'create'} $body
     * @return list<array<string, mixed>>
     */
    public function create(array $body, ?string $idempotencyKey = null): array
    {
        return $this->items($this->transport->request('POST', '/v1/customers', body: $body, idempotencyKey: $idempotencyKey));
    }

    /**
     * Gets a customer by their id.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->object($this->transport->request('GET', Transport::path('/v1/customers/{id}', ['id' => $id])));
    }

    /**
     * Lists customers, newest first. Loop over the page for all of them.
     *
     * @param array{q?: string, segment?: string, notInSegment?: string, sort?: string, limit?: int} $query
     */
    public function list(array $query = []): Page
    {
        return $this->page('/v1/customers', $query);
    }

    /**
     * Changes a customer. What's left out stays as it was; `attributes`, when sent, replace theirs.
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    public function update(string $id, array $changes): array
    {
        return $this->object($this->transport->request('PATCH', Transport::path('/v1/customers/{id}', ['id' => $id]), body: $changes));
    }

    /**
     * Deletes a customer.
     */
    public function delete(string $id): void
    {
        $this->transport->request('DELETE', Transport::path('/v1/customers/{id}', ['id' => $id]));
    }
}
