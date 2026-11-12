<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Transport;

/**
 * Segments: groups of customers, picked by hand or filled by rules.
 */
final class Segments extends Resource
{
    /** The customers in a segment. */
    public readonly SegmentCustomers $customers;

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->customers = new SegmentCustomers($transport);
    }

    /**
     * Adds a segment. With `rules`, it fills itself with the customers they match.
     *
     * @param array<string, mixed> $segment
     * @return array<string, mixed>
     */
    public function create(array $segment): array
    {
        return $this->object($this->transport->request('POST', '/v1/segments', body: $segment));
    }

    /**
     * Gets a segment by its id.
     *
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->object($this->transport->request('GET', Transport::path('/v1/segments/{id}', ['id' => $id])));
    }

    /**
     * Lists every segment, by name.
     *
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->items($this->transport->request('GET', '/v1/segments'));
    }

    /**
     * Changes a segment. What's left out stays as it was.
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    public function update(string $id, array $changes): array
    {
        return $this->object($this->transport->request('PATCH', Transport::path('/v1/segments/{id}', ['id' => $id]), body: $changes));
    }

    /**
     * Deletes a segment. Its customers stay.
     */
    public function delete(string $id): void
    {
        $this->transport->request('DELETE', Transport::path('/v1/segments/{id}', ['id' => $id]));
    }
}
