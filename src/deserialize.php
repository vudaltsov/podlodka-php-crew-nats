<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use function Thesis\exceptionally;

/**
 * @template T of object
 * @param class-string<T> $class
 * @return T
 */
function deserialize(?string $payload, string $class): object
{
    if ($payload === null) {
        throw new \UnexpectedValueException('No payload');
    }

    $result = exceptionally(static fn() => unserialize($payload));

    if (!$result instanceof $class) {
        throw new \UnexpectedValueException("Expected {$class}, got " . get_debug_type($result));
    }

    return $result;
}
