<?php

declare(strict_types=1);

namespace Goomzo\Exception;

use RuntimeException;
use Throwable;

/**
 * No answer came: the network failed, or the request took longer than the timeout.
 */
final class ConnectionException extends RuntimeException implements GoomzoException
{
    public function __construct(string $message, public readonly bool $timedOut, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
