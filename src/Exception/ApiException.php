<?php

declare(strict_types=1);

namespace Goomzo\Exception;

use RuntimeException;

/**
 * The API answered with an error.
 *
 * `code` says what went wrong, such as `invalid`, `not_found` or `insufficient_sms`. `fields` names the fields that
 * were wrong, such as `customers[1].phone`. Include `requestId` when you contact Goomzo.
 */
final class ApiException extends RuntimeException implements GoomzoException
{
    /**
     * @param array<string, array{code: string, message: string}> $fields
     */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $fields = [],
        public readonly ?string $requestId = null,
    ) {
        parent::__construct($message, $status);
    }
}
