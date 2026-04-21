<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

final readonly class Message
{
    /**
     * @param non-empty-string $sender
     * @param non-empty-string $text
     */
    public function __construct(
        public string $sender,
        public string $text,
    ) {}
}
