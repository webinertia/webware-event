<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Event package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Event\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

use function get_debug_type;
use function sprintf;

/**
 * A configured listener entry cannot be turned into a callable.
 *
 * Implements the PSR-11 container exception contract, so callers catching the
 * documented `ContainerExceptionInterface` from the aggregate factory cover it.
 *
 * @api
 */
final class InvalidListenerConfigurationException extends RuntimeException implements ContainerExceptionInterface
{
    public static function forInvalidEntry(mixed $entry): self
    {
        return new self(sprintf(
            'A listener entry must be a container service id or a callable; received %s.',
            get_debug_type($entry),
        ));
    }

    public static function forUnresolvableService(string $service): self
    {
        return new self(sprintf(
            'Listener service "%s" is not registered in the container.',
            $service,
        ));
    }
}
