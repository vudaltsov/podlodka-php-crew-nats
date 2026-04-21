<?php

declare(strict_types=1);

namespace Podlodka\PhpCrew\Nats;

use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\WritableResourceStream;
use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;

final readonly class Cli
{
    public static function print(string $bytes): void
    {
        /** @var WritableResourceStream */
        static $stdout = getStdout();

        $stdout->write($bytes);
    }

    public static function printLn(string $bytes = ''): void
    {
        self::print($bytes . PHP_EOL);
    }

    /**
     * @param non-empty-string $question
     * @param non-empty-string $pattern
     * @return non-empty-string
     */
    public static function ask(string $question, string $pattern, string $default = ''): string
    {
        /** @var ReadableResourceStream */
        static $stdin = getStdin();

        $defaultText = $default === '' ? '' : " [{$default}]";
        $questionText = "{$question} ({$pattern}){$defaultText}: ";

        self::print("{$question} ({$pattern}){$defaultText}: ");

        while (true) {
            $answer = trim($stdin->read() ?? '');

            if ($answer === '') {
                $answer = $default;
            }

            if ($answer !== '' && preg_match("/^{$pattern}$/", $answer) === 1) {
                return $answer;
            }

            self::print("Неверный ответ. {$questionText}");
        }
    }

    private function __construct() {}
}
