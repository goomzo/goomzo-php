<?php

declare(strict_types=1);

namespace Goomzo\Resources;

final class WhatsAppTemplates extends Resource
{
    /**
     * Lists the templates on the WhatsApp account, by name. `['status' => 'approved']` keeps the ones that can go now.
     *
     * @param array{status?: 'approved'} $query
     * @return list<array<string, mixed>>
     */
    public function list(array $query = []): array
    {
        return $this->items($this->transport->request('GET', '/v1/whatsapp/templates', query: $query));
    }
}
