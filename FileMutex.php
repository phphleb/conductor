<?php
declare(strict_types=1);
/**
 * @author  Foma Tuturov <fomiash@yandex.ru>
 */

/**
 * Class for implementation of mutexes based on activation of file locks flock(..., LOCK_EX) in tag files.
 * If the system fails to support file lock functions, or file locks fail to work for other reasons,
 * the check of availability/absence of the tag file will be used as a file lock/unlock fact.
 * In the tag file itself, the maximum time period for locking is set, upon expiration of which it will be removed.
 *
 * P.S. Queues where other processes are waiting to be unlocked are NON-SEQUENTIAL.
 * P.P.S. Files of a mutex type are usually applied only for one backend server; otherwise,
 * you can try to synchronize the folder with the tag files of mutexes. However, if it is possible,
 * it will be better to use mutexes based on storing the tags in the data base.
*/
 /**
 * Класс для реализации мьютексов, основанных на работе файловых блокировок flock(..., LOCK_EX) в файлах-метках.
 * Если система не поддерживает файловые блокировки, или по иным причинам файловая блокировка не работает,
 * будет использоваться проверка существования/отсутствия файла-метки как факт блокировки/разблокировки.
 * В самой файловой метке устанавливается период времени для максимальной блокировки, после которого она удаляется.
 *
 * P.S. Очереди, когда другие процессы ожидают разблокировки, являются НЕПОСЛЕДОВАТЕЛЬНЫМИ.
 * P.P.S Файловый тип мьютексов применяется обычно только для одного backend-сервера, иначе можно попробовать
 * синхронизацию папки с файлами-метками мьютексов. Но, если есть такая вероятность, лучше использовать
 * мьютексы основанные на хранении меток в базе данных.
 */

namespace Phphleb\Conductor;

use Phphleb\Conductor\Src\Scheme\{OriginMutexInterface, BaseConfigInterface};
use Phphleb\Conductor\Src\{Config\FileConfig, MutexDirector, OriginMutex};
use Phphleb\Conductor\Src\Storage\File\FileStorage;


class FileMutex extends MutexDirector
{
    protected static ?BaseConfigInterface $config = null;

    /**
     * @inheritDoc
     */
    public function __construct(?BaseConfigInterface $config = null)
    {
        if (is_null(self::$config)) {
            self::$config = is_null($config) ? new FileConfig() : $config;
        }
    }

    /**
     * @inheritDoc
     */
    protected function createMutex(string $name): OriginMutexInterface
    {
        return new OriginMutex(new FileStorage($name,  self::$config));
    }

}

