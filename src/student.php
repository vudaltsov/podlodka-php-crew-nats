<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use Amp\TimeoutCancellation;
use Thesis\Nats\Client;
use Thesis\Nats\Config;
use Thesis\Nats\Message;
use function Amp\async;
use function Amp\Future\await;

require_once __DIR__ . '/../vendor/autoload.php';

$natsCore = new Client(Config::fromURI('tcp://nats:4222?no_responders=true'));

$coroutines = [];

for ($i = 0; $i < 1_000; ++$i) {
    $coroutines[] = async(static function () use ($natsCore, $i): void {
        $a = random_int(0, 100);
        $b = random_int(0, 100);

        $sum = $natsCore
            ->request('math.add', new Message(serialize(new Args($a, $b))), new TimeoutCancellation(10))
            ->message
            ->payload;

        $product = $natsCore
            ->request('math.multiply', new Message(serialize(new Args($a, $b))))
            ->message
            ->payload;

        Cli::printLn(
            <<<TXT
                Problem #{$i}
                
                \$a = {$a}
                \$b = {$b}
                \$a + \$b = {$sum}
                \$a * \$b = {$product}
                -----------------------  
                TXT,
        );
    });
}

await($coroutines);
