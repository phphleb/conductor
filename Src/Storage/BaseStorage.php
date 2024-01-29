<?php
/**
 * Template class for declaring methods for working with mutexes.
 *
 * Класс-шаблон для декларации методов работы с мьютексами.
 */

namespace Phphleb\Conductor\Src\Storage;


use Phphleb\Conductor\Src\Scheme\BaseConfigInterface;

abstract class BaseStorage
{
    abstract public function __construct(string $mutexId, BaseConfigInterface $config);

    /**
     * Returns the current config.
     *
     * Возвращает текущий конфиг.
     *
     * @return BaseConfigInterface
     */
    abstract public function getConfig(): BaseConfigInterface;

    /**
     * Returns the result of locking a current mutex.
     *
     * Возвращает результат блокировки текущего мьютекса.
     *
     * @param int$unlockSeconds - sets the maximum blocking time in seconds.
     *                          - устанавливает максимальное время блокировки в секундах.
     *
     *
     * @param int $revisionTime - sets the end time of the mutex lock.
     *                          - устанавливает время окончания блокировки мьютекса.
     *
     * @return bool
     */
    abstract public function lockTag(int $unlockSeconds, int $revisionTime): bool;

    /**
     * Returns the result of checking for an already locked mutex with the same name.
     *
     * Возвращает результат проверки на наличие уже заблокированного мьютекса с таким названием.
     *
     * @return bool
     */
    abstract public function checkTagExists(): bool;

    /**
     * Returns the result of checking for the presence of the current mutex in storage.
     *
     * Возвращает результат проверки на наличие текущего мьютекса в хранилище.
     *
     * @return bool
     */
    abstract public function checkLockedTagExists(): bool;

    /**
     * Returns the result after unlocking the current mutex.
     *
     * Возвращает результат после разблокировки текущего мьютекса.
     *
     * @return bool
     */
    abstract public function unlockTag(): bool;

}

