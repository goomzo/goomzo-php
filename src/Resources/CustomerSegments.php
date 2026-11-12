<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Transport;

final class CustomerSegments extends Resource
{
    /**
     * Lists the segments a customer is in: picked by hand, and ones whose rules they match.
     *
     * @return list<array<string, mixed>>
     */
    public function list(string $customerId): array
    {
        return $this->items($this->transport->request('GET', Transport::path('/v1/customers/{id}/segments', ['id' => $customerId])));
    }

    /**
     * Puts a customer in exactly these segments, by name, adding ones that don't exist yet.
     *
     * @param list<string> $names
     * @return list<array<string, mixed>>
     */
    public function set(string $customerId, array $names): array
    {
        $route = Transport::path('/v1/customers/{id}/segments', ['id' => $customerId]);

        return $this->items($this->transport->request('PUT', $route, body: ['names' => $names]));
    }
}
