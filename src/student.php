<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use Thesis\Nats\Client;
use Thesis\Nats\Config;
use Thesis\Nats\Message;

require_once __DIR__ . '/../vendor/autoload.php';

$natsCore = new Client(Config::fromURI('tcp://nats:4222'));

$result = $natsCore->request('math.multiply', new Message(serialize(new Args(1, 2))));

Cli::printLn($result->message->payload);
