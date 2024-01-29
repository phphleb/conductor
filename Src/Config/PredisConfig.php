<?php
/**
 * Base class for configuring mutexes from the Redis(Predis) (for the HLEB framework).
 *
 * Базовый класс конфигурации мьютексов из Redis(Predis)  (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Config;


use Phphleb\Conductor\Src\Scheme\BaseConfigInterface;
use Phphleb\Conductor\Src\Scheme\PredisConfigInterface;

class PredisConfig implements PredisConfigInterface, BaseConfigInterface
{
    protected const MAX_LOCK_TIME = 14400;

    protected const QUEUE_WAIT_INTERVAL = 100_000;

    protected const MUTEX_PREFIX = 'mutex_auto_tags';

    protected array $parameters = [];

    protected array $options = [];

    /**
     * Returns the maximum number of seconds that a lock can be held.
     *
     * Возвращает максимальное количество секунд на которое может быть произведена блокировка.
     *
     * @return int
     */
    #[\Override]
    public function getMaxLockTime(): int
    {
        return self::MAX_LOCK_TIME;
    }

    /**
     * Returns how long a process waits in the queue between checking a lock.
     * Value in microseconds.
     *
     * Возвращает интервал ожидания процесса в очереди между проверкой блокировки.
     * Значение в микросекундах.
     *
     * @return int
     */
    #[\Override]
    public function getQueueWaitIntervalInUs(): int
    {
        return self::QUEUE_WAIT_INTERVAL;
    }

    #[\Override]
    public function getMutexPrefix(): string
    {
        return self::MUTEX_PREFIX;
    }

    #[\Override]
    public function getParameters(): array
    {
        return $this->parameters;
    }

    #[\Override]
    public function getOptions(): array
    {
        return $this->options;
    }
}


