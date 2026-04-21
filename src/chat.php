<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use Thesis\Nats\Client;
use Thesis\Nats\Config;
use Thesis\Nats\Delivery;
use Thesis\Nats\Message as NatsMessage;
use function Amp\async;
use function Amp\ByteStream\getStdin;
use function Amp\Future\awaitFirst;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';

$id = Cli::ask('Chat ID', '\w+');
$name = Cli::ask('Your nickname', '\w+');

$natsCore = new Client(Config::fromURI('tcp://nats:4222'));

$subscription = $natsCore->subscribe("chat.{$id}", static function (Delivery $delivery): void {
    $message = deserialize($delivery->message->payload, Message::class);

    Cli::printLn(
        <<<TXT
            {$message->sender}: {$message->text}
            ⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻
            TXT,
    );
});

$input = async(static function () use ($name, $natsCore, $id): void {
    $stdin = getStdin();

    while (null !== $text = $stdin->read()) {
        $text = trim($text);

        if ($text === '') {
            continue;
        }

        $natsCore->publish("chat.{$id}", new NatsMessage(
            serialize(new Message($name, $text)),
        ));
    }
});

Cli::printLn('Ready!');

awaitFirst([
    $input,
    async(static fn() => trapSignal([SIGINT, SIGTERM])),
]);

$subscription->drain();
$subscription->awaitCompletion();

$natsCore->stop();
