<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use Amp\Future;
use Thesis\Nats\Client;
use Thesis\Nats\Config;
use Thesis\Nats\Delivery;
use Thesis\Nats\Message;
use function Amp\async;
use function Amp\delay;
use function Amp\trapSignal;

require_once __DIR__ . '/../vendor/autoload.php';

$natsCore = new Client(Config::fromURI('tcp://nats:4222'));

/** @var array<int, Future<void>> */
$coroutines = [];

$subscription = $natsCore->subscribe('math.*', static function (Delivery $delivery) use (&$coroutines): void {
    $coroutines[spl_object_id($delivery)] = async(static function () use ($delivery): void {
        Cli::printLn('Incoming math problem');

        delay(random_int(0, 100) / 100);

        $args = deserialize($delivery->message->payload, Args::class);

        $result = match ($delivery->subject) {
            'math.add' => $args->a + $args->b,
            'math.multiply' => $args->a * $args->b,
        };

        $delivery->reply(new Message((string) $result));

        Cli::printLn('Problem solved');
    })->catch(static function (\Throwable $error) use ($delivery): void {
        Cli::printLn('Error: ' . $error->getMessage());

        $delivery->reply(new Message('Error: ' . $error->getMessage()));
    })->finally(static function () use (&$coroutines, $delivery): void {
        unset($coroutines[spl_object_id($delivery)]);
    });
});

Cli::printLn('Ready');

trapSignal([SIGINT, SIGTERM]);

$subscription->drain();
$subscription->awaitCompletion();

Future\await($coroutines);

$natsCore->stop();
