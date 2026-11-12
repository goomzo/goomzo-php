<?php

declare(strict_types=1);

namespace Goomzo\Resources;

use Goomzo\Transport;

/**
 * The business's WhatsApp account.
 */
final class WhatsApp
{
    /** The templates on the account, as synced from Meta. */
    public readonly WhatsAppTemplates $templates;

    public function __construct(Transport $transport)
    {
        $this->templates = new WhatsAppTemplates($transport);
    }
}
