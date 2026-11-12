<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Page;
use Goomzo\Transport;

final class CustomerActivity extends Resource
{
    /**
     * Records a customer's activity, by their id. To find them by phone or email, use `$goomzo->activity->record()`.
     *
     * @param array{activities: list<array<string, mixed>>} $body
     * @return array{customerId: string, items: list<array<string, mixed>>}
     */
    public function record(string $customerId, array $body, ?string $idempotencyKey = null): array
    {
        $route = Transport::path('/v1/customers/{id}/activity', ['id' => $customerId]);
        /** @var array{customerId: string, items: list<array<string, mixed>>} $recorded */
        $recorded = $this->transport->request('POST', $route, body: $body, idempotencyKey: $idempotencyKey);

        return $recorded;
    }

    /**
     * Lists a customer's activity, newest first.
     *
     * @param array{limit?: int} $query
     */
    public function list(string $customerId, array $query = []): Page
    {
        return $this->page(Transport::path('/v1/customers/{id}/activity', ['id' => $customerId]), $query);
    }
}
