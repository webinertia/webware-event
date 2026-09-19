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

namespace WebwareTest\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Webware\Event\Exception\InvalidListenerConfigurationException;

#[CoversClass(InvalidListenerConfigurationException::class)]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forInvalidEntry')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableService')]
final class InvalidListenerConfigurationExceptionTest extends TestCase
{
    #[Test]
    public function forInvalidEntryDescribesTheReceivedType(): void
    {
        $exception = InvalidListenerConfigurationException::forInvalidEntry(['priority' => 5]);

        static::assertSame(
            'A listener entry must be a container service id or a callable; received array.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forUnresolvableServiceNamesTheServiceId(): void
    {
        $exception = InvalidListenerConfigurationException::forUnresolvableService('not.registered.Service');

        static::assertSame(
            'Listener service "not.registered.Service" is not registered in the container.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function itSatisfiesTheContainerExceptionContract(): void
    {
        $exception = InvalidListenerConfigurationException::forInvalidEntry(null);

        static::assertInstanceOf(RuntimeException::class, $exception);
        static::assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }
}
