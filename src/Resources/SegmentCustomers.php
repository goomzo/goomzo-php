<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Page;
use Goomzo\Transport;

final class SegmentCustomers extends Resource
{
    /**
     * Lists a segment's customers. Loop over the page for all of them.
     *
     * @param array{limit?: int} $query
     */
    public function list(string $segmentId, array $query = []): Page
    {
        return $this->page(Transport::path('/v1/segments/{id}/customers', ['id' => $segmentId]), $query);
    }

    /**
     * Adds up to 1,000 customers, by id, to a segment picked by hand.
     *
     * @param list<string> $customerIds
     * @return array<string, mixed>
     */
    public function add(string $segmentId, array $customerIds): array
    {
        $route = Transport::path('/v1/segments/{id}/customers', ['id' => $segmentId]);

        return $this->object($this->transport->request('POST', $route, body: ['customers' => $customerIds]));
    }

    /**
     * Takes up to 1,000 customers, by id, out of a segment picked by hand.
     *
     * @param list<string> $customerIds
     * @return array<string, mixed>
     */
    public function remove(string $segmentId, array $customerIds): array
    {
        $route = Transport::path('/v1/segments/{id}/customers/remove', ['id' => $segmentId]);

        return $this->object($this->transport->request('POST', $route, body: ['customers' => $customerIds]));
    }
}
