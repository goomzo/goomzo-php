<?php

declare(strict_types=1);

namespace Goomzo\Resources;

/**
 * Customer activity, recorded by phone, email or id.
 */
final class Activity extends Resource
{
    /**
     * Records up to 100 things a customer did, all or none. Give exactly one of `phone`, `email` or `customerId`. No
     * customer with it throws ApiException with `not_found`, and nobody is added.
     *
     *     $goomzo->activity->record(['phone' => '+255712345678', 'activities' => [['key' => 'purchased', 'value' => 'Shoes']]]);
     *
     * @param array{activities: list<array<string, mixed>>, phone?: string, email?: string, customerId?: string} $body
     * @return array{customerId: string, items: list<array<string, mixed>>}
     */
    public function record(array $body, ?string $idempotencyKey = null): array
    {
        /** @var array{customerId: string, items: list<array<string, mixed>>} $recorded */
        $recorded = $this->transport->request('POST', '/v1/activity', body: $body, idempotencyKey: $idempotencyKey);

        return $recorded;
    }
}
