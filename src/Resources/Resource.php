<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Page;
use Goomzo\Transport;

/**
 * @internal
 */
abstract class Resource
{
    public function __construct(protected readonly Transport $transport) {}

    /**
     * @return array<string, mixed>
     */
    protected function object(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function items(mixed $data): array
    {
        /** @var list<array<string, mixed>> $items */
        $items = is_array($data) ? ($data['items'] ?? []) : [];

        return $items;
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function page(string $route, array $query): Page
    {
        $fetch = function (?string $cursor = null) use (&$fetch, $route, $query): Page {
            $data = $this->transport->request('GET', $route, query: [...$query, 'cursor' => $cursor]);

            return new Page($this->object($data), $fetch);
        };

        return $fetch();
    }
}
