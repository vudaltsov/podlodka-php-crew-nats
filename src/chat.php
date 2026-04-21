<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use Thesis\Nats\Client;
use Thesis\Nats\Config;
use Thesis\Nats\JetStream\Api\ConsumerConfig;
use Thesis\Nats\JetStream\Api\DeliverPolicy;
use Thesis\Nats\JetStream\Api\StreamConfig;
use Thesis\Nats\JetStream\Delivery;
use Thesis\Nats\Message as NatsMessage;
use function Amp\async;
use function Amp\ByteStream\getStdin;
use function Amp\Future\awaitFirst;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';

Cli::printLn('Привет! Добро пожаловать в NATS-чат!' . PHP_EOL);

$id = Cli::ask('ID чата', '\w+', $argv[1] ?? '');
$name = Cli::ask('Твой никнейм', '\w+');
$deliveryPolicy = DeliverPolicy::from(Cli::ask('Что подгрузить', 'all|last|new', 'all'));
Cli::printLn();

$uri = getenv('NATS');

if (!\is_string($uri) || $uri === '') {
    $uri = 'tcp://nats:4222';
}

$natsCore = new Client(Config::fromURI($uri));
$jetStream = $natsCore->jetStream();

$subscription = $jetStream
    ->createOrUpdateStream(new StreamConfig(
        name: 'CHATS',
        subjects: ['chat.*'],
    ))
    ->createOrUpdateConsumer(new ConsumerConfig(
        deliverPolicy: $deliveryPolicy,
        filterSubjects: [
            "chat.{$id}",
        ],
    ))
    ->pull(static function (Delivery $delivery): void {
        $message = deserialize($delivery->message->payload, Message::class);

        Cli::printLn(
            <<<TXT
                {$message->sender}: {$message->text}
                ⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻
                TXT,
        );

        $delivery->ack();
    });

$input = async(static function () use ($name, $jetStream, $id): void {
    $stdin = getStdin();

    while (null !== $text = $stdin->read()) {
        $text = trim($text);

        if ($text === '') {
            continue;
        }

        $jetStream->publish("chat.{$id}", new NatsMessage(
            serialize(new Message($name, $text)),
        ));
    }
});

Cli::printLn(
    <<<'TXT'
        Чат готов! Продуктивного общения :)
        ⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻⸻
        TXT,
);

awaitFirst([
    $input,
    async(static fn() => trapSignal([SIGINT, SIGTERM])),
]);

$subscription->drain();
$subscription->awaitCompletion();

$natsCore->stop();
