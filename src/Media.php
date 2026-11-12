<?php

declare(strict_types=1);

namespace Goomzo;

/**
 * A file a customer sent on WhatsApp.
 */
final class Media
{
    public function __construct(
        public readonly string $content,
        public readonly string $contentType,
    ) {}
}
