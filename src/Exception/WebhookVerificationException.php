<?php

declare(strict_types=1);

namespace Goomzo\Exception;

use RuntimeException;

/**
 * A webhook's signature or timestamp didn't check out. Don't trust its body.
 */
final class WebhookVerificationException extends RuntimeException implements GoomzoException {}
