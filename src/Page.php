<?php

declare(strict_types=1);

namespace Goomzo;

use Closure;
use Generator;
use IteratorAggregate;

/**
 * One page of a list. Loop over it for every item: the next pages are asked for as you go.
 *
 *     $page = $goomzo->customers->list(['limit' => 200]);
 *     $page->items; $page->nextCursor; $page->total;
 *
 *     foreach ($goomzo->customers->list() as $customer) {
 *         echo $customer['name'];
 *     }
 *
 * @implements IteratorAggregate<int, array<string, mixed>>
 */
final class Page implements IteratorAggregate
{
    /** @var list<array<string, mixed>> */
    public readonly array $items;

    /** The cursor of the next page, or null on the last. */
    public readonly ?string $nextCursor;

    /** How many match, on every page. Only customers, messages and broadcasts have it. */
    public readonly ?int $total;

    /**
     * @param array<string, mixed> $data
     * @param Closure(string): Page $fetch
     */
    public function __construct(array $data, private readonly Closure $fetch)
    {
        /** @var list<array<string, mixed>> $items */
        $items = $data['items'] ?? [];
        $this->items = $items;
        $this->nextCursor = isset($data['nextCursor']) && is_string($data['nextCursor']) ? $data['nextCursor'] : null;
        $this->total = isset($data['total']) && is_int($data['total']) ? $data['total'] : null;
    }

    /**
     * The next page, or null on the last.
     */
    public function nextPage(): ?Page
    {
        return $this->nextCursor === null ? null : ($this->fetch)($this->nextCursor);
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    public function getIterator(): Generator
    {
        $page = $this;
        while ($page !== null) {
            yield from $page->items;
            $page = $page->nextPage();
        }
    }
}
