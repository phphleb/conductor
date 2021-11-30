<?php
declare(strict_types=1);

/**
 * A template class for creating and managing mutex objects for inheritance.
 *
 * Класс-шаблон для создания и управления объектами мьютексов при наследовании.
 */

namespace Phphleb\Conductor\Src;

use Phphleb\Conductor\Src\Scheme\{MutexInterface, BaseConfigInterface, OriginMutexInterface};

abstract class MutexDirector implements MutexInterface
{
    protected static array $mutexList = [];

    /**
     * In the constructor, you can set your own configuration.
     *
     * В конструкторе можно установить собственную конфигурацию.
     *
     * @param BaseConfigInterface|null $config
     */
    abstract public function __construct(?BaseConfigInterface $config = null);

    /**
     * Factory method.
     * Returns an object with the `SpecificMutexInterface` interface.
     *
     * Фабричный метод.
     * Возвращает объект с интерфейсом `SpecificMutexInterface`.
     *
     * @param string $mutexName - is a custom unique name for the mutex.
     *                          - пользовательское уникальное название мьютекса.
     * @return OriginMutexInterface
     */
    abstract protected function createMutex(string $mutexName): OriginMutexInterface;

    /**
     * Returns the result of the lock performed.
     *
     * Возвращает результат произведенной блокировки.
     *
     * @param string $mutexName  - is a custom unique name for the mutex.
     *                           - пользовательское уникальное название мьютекса.
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
     * @throws MutexException
     */
    public function acquire(string $mutexName, ?int $unlockSeconds = null): bool
    {
        if (!isset(self::$mutexList[$mutexName])) {
            try {
                self::$mutexList[$mutexName] = $this->createMutex($mutexName);
                $this->init(self::$mutexList[$mutexName]);

                return self::$mutexList[$mutexName]->acquire($unlockSeconds);
            } catch (\Throwable $e) {
                throw new MutexException($mutexName, $e->getMessage(), $e->getCode(), $e);
            }
        }
        throw new MutexException($mutexName, "A mutex with a name `$mutexName` (method `acquire`) already initialized.");
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
     * @param string $mutexName - user-defined unique name of the mutex set in the `acquire` method.
     *                          - пользовательское уникальное название мьютекса, установленное в методе `acquire`.
     * @return bool
     * @throws MutexException
     */
    public function release(string $mutexName): bool
    {
        if (isset(self::$mutexList[$mutexName])) {
            try {
                return self::$mutexList[$mutexName]->release();
            } catch (\Throwable $e) {
                throw new MutexException($mutexName, $e->getMessage(), $e->getCode(), $e);
            }
        }
        throw new MutexException($mutexName, "A mutex with a name `$mutexName` (method `release`) has not been initialized.");
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
     * @param string $mutexName - user-defined unique name of the mutex set in the `acquire` method.
     *                          - пользовательское уникальное название мьютекса, установленное в методе `acquire`.
     * @return bool
     * @throws MutexException
     */
    public function unlock(string $mutexName): bool
    {
        if (isset(self::$mutexList[$mutexName])) {
            try {
                return self::$mutexList[$mutexName]->unlock();
            } catch (\Throwable $e) {
                throw new MutexException($mutexName, $e->getMessage(), $e->getCode(), $e);
            }
        }
        throw new MutexException($mutexName, "A mutex with a name `$mutexName` (method `unlock`) has not been initialized.");
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
     * @param string $mutexName - user-defined unique name of the mutex set in the `acquire` method.
     *                          - пользовательское уникальное название мьютекса, установленное в методе `acquire`.
     * @return bool
     * @throws MutexException
     */
    public function isIntercepted(string $mutexName): bool
    {
        if (isset(self::$mutexList[$mutexName])) {
            try {
                return self::$mutexList[$mutexName]->isIntercepted();
            } catch (\Throwable $e) {
                throw new MutexException($mutexName, $e->getMessage(), $e->getCode(), $e);
            }
        }
        throw new MutexException($mutexName, "A mutex with a name `$mutexName` (method `isIntercepted`) has not been initialized.");
    }

    /**
     * Returns the result of the lock timeout expired.
     *
     * Возвращает результат истечения блокировки по времени.
     *
     * @param string $mutexName - user-defined unique name of the mutex set in the `acquire` method.
     *                          - пользовательское уникальное название мьютекса, установленное в методе `acquire`.
     * @return bool
     * @throws MutexException
     */
    public function isCompleted(string $mutexName): bool
    {
        if (isset(self::$mutexList[$mutexName])) {
            try {
                return self::$mutexList[$mutexName]->isCompleted();
            } catch (\Throwable $e) {
                throw new MutexException($mutexName, $e->getMessage(), $e->getCode(), $e);
            }
        }
        throw new MutexException($mutexName, "A mutex with a name `$mutexName` (method `isCompleted`) has not been initialized.");
    }



    private function init(OriginMutexInterface $mutex): void
    {
        register_shutdown_function(function () use ($mutex) {
            if (is_null($mutex->getStatus())) {
                $mutex->unlock();
            }
        });
    }

}

