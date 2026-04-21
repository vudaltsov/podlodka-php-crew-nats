<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use Thesis\Nats\Client;
use Thesis\Nats\Config;
use Thesis\Nats\Delivery;
use Thesis\Nats\Message;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';

$natsCore = new Client(Config::fromURI('tcp://nats:4222'));

$subscription = $natsCore->subscribe('math.*', function (Delivery $delivery): void {
    try {
        Cli::printLn('Incoming math problem');

        $args = deserialize($delivery->message->payload, Args::class);

        $result = match ($delivery->subject) {
            'math.add' => $args->a + $args->b,
            'math.multiply' => $args->a * $args->b,
        };

        $delivery->reply(new Message((string) $result));

        Cli::printLn('Problem solved');
    } catch (\Throwable $error) {
        Cli::printLn('Error: '. $error->getMessage());

        $delivery->reply(new Message('Error: '.$error->getMessage()));
    }
});

Cli::printLn('Ready');

trapSignal([SIGINT, SIGTERM]);

$subscription->drain();
$subscription->awaitCompletion();

$natsCore->stop();
