<?php
declare(strict_types=1);

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
    protected StorageInterface $storage;

    protected BaseConfigInterface $config;

    protected bool $isReleased = false;

    protected ?bool $status = null;

    protected int $revisionTime = 0;

    protected int $unlockSeconds = 0;

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

    public function acquire(?int $unlockSeconds = null): bool
    {
        $this->unlockSeconds = $unlockSeconds >= 0 ?  (is_null($unlockSeconds) || $unlockSeconds > $this->config->getMaxLockTime() ? $this->config->getMaxLockTime() : $unlockSeconds) : 0;

        if (!is_null($this->status)) {
            throw new Exception('The method `acquire` has already been called.');
        }

        if(!$this->wait()) {
            return false;
        }

        $this->revisionTime = time() + $this->unlockSeconds;

        if ($this->unlockSeconds === 0) {
            return true;
        }

        $this->isReleased = $this->storage->lockTag($this->unlockSeconds, $this->revisionTime);

        if ($this->isReleased) {
            usleep($this->config->getQueueWaitIntervalInUs());
            $this->isReleased = $this->storage->checkLockedTagExists();
        }

        return  $this->isReleased;
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
    public function release(): bool
    {
        if ($this->unlockSeconds === 0) {
            return true;
        }
        if (is_bool($this->status)) {
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
    public function unlock(): bool
    {
        if ($this->unlockSeconds === 0) {
            return true;
        }
        if (is_bool($this->status)) {
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
    public function isIntercepted(): bool
    {
        if ($this->unlockSeconds === 0) {
            return false;
        }
        if (is_bool($this->status)) {
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
    public function isCompleted(): bool
    {
        return time() > $this->revisionTime;
    }

    /**
     * Returns the internal state of the current mutex. null - not implemented, true/false - successful/unsuccessful implementation.
     *
     * Возвращает внутреннее состояние текущего мьютекса. null - не реализован, true/false - успешная/неуспешная реализация.
     *
     * @return bool|null
     */
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
        try {
            if ($this->storage->checkTagExists()) {
                usleep($this->config->getQueueWaitIntervalInUs());
                $this->wait();
            }
        } catch (\Throwable $e) {
            return false;
        }
        return true;
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
        return $this->storage->unlockTag();
    }
}

