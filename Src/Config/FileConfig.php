<?php
declare(strict_types=1);

/**
 * Class with a basic file configuration template (for the HLEB framework).
 *
 * Класс с базовым шаблоном файловой конфигурации (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Config;

use Phphleb\Conductor\Src\Scheme\BaseConfigInterface;
use Phphleb\Conductor\Src\Scheme\FileConfigInterface;

class FileConfig implements FileConfigInterface, BaseConfigInterface
{
    protected const MAX_LOCK_TIME = 14400;

    protected const STORAGE_BASE_PATH = HLEB_GLOBAL_DIRECTORY . '/storage/lib/mutex/tags';

    protected const FILE_EXTENSION = '.txt';

    protected const QUEUE_WAIT_INTERVAL = 100_000;

    /**
     * Returns the maximum number of seconds that a lock can be held.
     *
     * Возвращает максимальное количество секунд на которое может быть произведена блокировка.
     *
     * @return int
     */
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
    public function getQueueWaitIntervalInUs(): int
    {
       return self::QUEUE_WAIT_INTERVAL;
    }

    /**
     * Returns a link to the folder with the stored mutex tag files.
     *
     * Возвращает ссылку на папку с сохраняемыми файлами-метками мьютексов.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return self::STORAGE_BASE_PATH;
    }

    /**
     * Returns the extension of the tag files for mutexes.
     *
     * Возвращает расширение файлов-меток для мьютексов.
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return self::FILE_EXTENSION;
    }

}

