<?php
/**
 * Class for the general operation of mutexes.
 *
 * Класс общей работы мьютексов.
 */

namespace Phphleb\Conductor\Src;

use Phphleb\Conductor\Src\Scheme\{BaseConfigInterface, OriginMutexInterface, StorageInterface};
use Exception;

class OriginMutex implements OriginMutexInterface
{
    private const TIME_FACTOR = 2;

    private const MIN_PAUSE = 10;

    protected StorageInterface $storage;

    protected BaseConfigInterface $config;

    protected bool $isReleased = false;

    protected ?bool $status = null;

    protected int $revisionTime = 0;

    protected int $unlockSeconds = 0;

    protected float $startInterval = 0;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
        $this->config = $storage->getConfig();
    }

    /**
     * Returns the result of the lock performed.
     *
     * Возвращает результат произведенной блокировки.
     *
     * @param int|null $unlockSeconds - sets the maximum blocking time in seconds.
     *
     *   not specified - the maximum value from the configuration.
     *   0 - cancels any blocking.
     *   14400 is the maximum default value (from configuration).
     *
     *                                 - устанавливает максимальное время блокировки в секундах.
     *
     *   не указано - максимальное значение из конфигурации.
     *   0 - отмена всякой блокировки.
     *   14400 - максимальное значение по умолчанию (из конфигурации).
     *
     * @return bool
     * @throws Exception
     */
    #[\Override]
    public function acquire(?int $unlockSeconds = null): bool
    {
        $this->unlockSeconds = $unlockSeconds >= 0 ?  (\is_null($unlockSeconds) || $unlockSeconds > $this->config->getMaxLockTime() ? $this->config->getMaxLockTime() : $unlockSeconds) : 0;

        if (!\is_null($this->status)) {
            throw new Exception('The method `acquire` has already been called.');
        }
        if ($this->unlockSeconds === 0) {
            return true;
        }

        return  $this->isReleased = $this->waitAndLock();
    }

    /**
     * Returns the result of unlocking the mutex.
     * If blocking/unblocking succeeds, it returns `true`,
     * otherwise` false`, and if the maximum blocking time is exceeded, it returns `false`.
     *
     * Возвращает результат разблокировки мьютекса.
     * При успешном прохождении блокировки/разблокировки возвращается `true`, иначе `false`,
     * а при превышении максимального времени блокировки возвращает `false`.
     *
     * @return bool
     */
    #[\Override]
    public function release(): bool
    {
        if ($this->unlockSeconds === 0) {
            return true;
        }
        if (\is_bool($this->status)) {
            return $this->status;
        }
        if ($this->isCompleted()) {
            $this->close();
            return $this->status = false;
        }
        return $this->close();
    }

    /**
     * Force unlocking a mutex.
     * Method name may indicate extreme use,
     * and upon successful completion of blocking/unblocking, it returns `true`, otherwise` false`.
     *
     * Принудительная разблокировка мьютекса.
     * Название метода может указывать на чрезвычайное использование,
     * а при успешном прохождении блокировки/разблокировки возвращается `true`, иначе `false`.
     *
     * @return bool
     */
    #[\Override]
    public function unlock(): bool
    {
        if ($this->unlockSeconds === 0) {
            return true;
        }
        if (\is_bool($this->status)) {
            return $this->status;
        }
        return $this->close();
    }

    /**
     * Returns `false` if there have been no concurrent requests since the beginning of the blocking
     * by this process (even after the blocking ended) and `true` if the mutex was accessed by another process.
     * After successful execution of the `release` methods or `unlock` always returns `false`.
     *
     * Возвращает `false` если конкурентных запросов не было с начала блокировки этим процессом (даже по её окончании)
     * и `true`, если к мьютексу обращался/обратился другой процесс. После успешного выполнения методов `release`
     * или `unlock` всегда возвращает `false`.
     *
     * @return bool
     */
    #[\Override]
    public function isIntercepted(): bool
    {
        if ($this->unlockSeconds === 0) {
            return false;
        }
        if (\is_bool($this->status)) {
            return !$this->status;
        }
        return !$this->storage->checkLockedTagExists();
    }

    /**
     * Returns the result of the lock timeout expired.
     *
     * Возвращает результат истечения блокировки по времени.
     *
     * @return bool
     */
    #[\Override]
    public function isCompleted(): bool
    {
        return \time() > $this->revisionTime;
    }

    /**
     * Returns the internal state of the current mutex. null - not implemented, true/false - successful/unsuccessful implementation.
     *
     * Возвращает внутреннее состояние текущего мьютекса. null - не реализован, true/false - успешная/неуспешная реализация.
     *
     * @return bool|null
     */
    #[\Override]
    public function getStatus(): ?bool
    {
        return $this->status;
    }

    /**
     * Returns the result of one iteration of waiting for the mutex to be freed.
     *
     * Возвращает результат одной итерации ожидания освобождения мьютекса.
     *
     * @return bool
     */
    protected function wait(): bool
    {
        while (true) {
            $this->startInterval = \microtime(true);
            try {
                if (!$this->storage->checkTagExists()) {
                    return true;
                }
            } catch (\Throwable $e) {
                return false;
            }
            \usleep($this->config->getQueueWaitIntervalInUs());
        }
    }

    /**
     * Returns the result of deactivating the mutex.
     *
     * Возвращает результат деактивации мьютекса.
     *
     * @return bool
     */
    protected function close(): bool
    {
        if (!$this->isReleased) {
            return $this->status = false;
        }

        return $this->storage->checkLockedTagExists() && $this->storage->unlockTag();
    }

    /**
     * Returns the wait result, in microseconds, which is equal to the interval between two actions.
     *
     * Возвращает результат ожидания в микросекундах, который равен интервалу между двумя действиями.
     *
     * @return int
     */
    protected function pause(): int
    {
        $us = (int)((\microtime(true) - $this->startInterval) * 1_000_000 * self::TIME_FACTOR);
        if ($us < self::MIN_PAUSE) {
            $us = self::MIN_PAUSE;
        }
        \usleep($us);

        return $us;
    }

    /**
     * Returns the result of locking the mutex after waiting in the queue.
     *
     * Возвращает результат блокировки мьютекса после ожидания в очереди.
     *
     * @return bool
     */
    private function waitAndLock(): bool
    {
        while (true) {
            if (!$this->wait()) {
                return false;
            }
            $this->revisionTime = \time() + $this->unlockSeconds;
            if (!$this->storage->lockTag($this->unlockSeconds, $this->revisionTime)) {
                return false;
            }
            $this->pause();
            if ($this->storage->checkLockedTagExists()) {
                return true;
            }
        }
    }
}

