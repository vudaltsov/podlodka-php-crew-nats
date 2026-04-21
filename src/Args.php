<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

final readonly class Args
{
    public function __construct(
        public int $a,
        public int $b,
    ) {}
}
