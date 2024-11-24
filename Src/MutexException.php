<?php
/**
 * Class for individual errors of the mutex system.
 *
 * Класс для индивидуальных ошибок механизма работы мьютексов.
 */

namespace Phphleb\Conductor\Src;

class MutexException extends \Exception
{
    public function __construct(string $mutexName, string $message, $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct("[Mutex name: $mutexName] $message", $code, $previous);
    }

    #[\Override]
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

