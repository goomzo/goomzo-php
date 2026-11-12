<?php

declare(strict_types=1);

namespace Goomzo\Exception;

use Throwable;

/**
 * Every exception the SDK throws. Catch this to handle any of them.
 */
interface GoomzoException extends Throwable {}
