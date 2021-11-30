<?php
declare(strict_types=1);

/**
 * Class for managing tag files for mutexes.
 *
 * Класс для управления файлами-метками у мьютексов.
 */

namespace Phphleb\Conductor\Src\Storage\File;

use Phphleb\Conductor\Src\Scheme\FileConfigInterface;
use Phphleb\Conductor\Src\Config\{FileConfig};
use Phphleb\Conductor\Src\Tags\Tag;

class TagFileManager
{
    protected const DELIMITER = ':';

    protected FileConfigInterface $config;

    public function __construct(FileConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Returns a rendered object from a file.
     * 
     * Возвращает сформированный объект из файла.
     *
     * @param string $file - path to the file.
     *                     - путь до файла.
     * @return Tag|null
     */
    public function getTagData(string $file): ?Tag
    {
        return $this->stringToTagData((string)$this->getFileContent($file));

    }

    /**
     * Returns a string of unified data from an object.
     *
     * Возвращает строку унифицированных данных из объекта.
     *
     * @param Tag $tag
     * @return string
     */
    public function tagToStringData(Tag $tag): string
    {
        return implode(self::DELIMITER, [$tag->getRevisionTime(), $tag->getUnlockSeconds(), $tag->getHash(), $tag->getName()]);
    }

    /**
     * Returns an object by parameters.
     *
     * Возвращает объект по параметрам.
     *
     * @param int $revisionTime  - the Unix system timestamp when the lock was completed.
     *                           - метка системного времени Unix завершения блокировки.
     *
     * @param int $unlockSeconds - the number of seconds to block.
     *                           - количество секунд блокировки.
     *
     * @param string $hash       - identifier of the current process.
     *                           - идентификатор текущего процесса.
     *
     * @param string $name       - custom mutex name.
     *                           - пользовательское название мьютекса.
     *
     * @return Tag
     */

    public function valuesToTagData(int $revisionTime, int $unlockSeconds, string $hash, string $name): Tag
    {
        return new Tag($revisionTime, $unlockSeconds, $hash, $name);
    }

    /**
     * Returns object from an attempt to parse a string.
     *
     * Возвращает сформированный объект из попытки разбора строки.
     *
     * @param string $content
     * @return Tag|null
     */
    public function stringToTagData(string $content): ?Tag
    {
        if ($content) {
            $data = explode(self::DELIMITER, $content);
            if (count($data) === 4) {
                return $this->valuesToTagData(
                    (int)array_shift($data),
                    (int)array_shift($data),
                    (string)array_shift($data),
                    (string)implode(self::DELIMITER, $data)
                );
            }
        }
        return null;
    }


    /**
     * Find and remove unused mutex tag files,
     * with unused names or blocked before the maximum blocking time.
     *
     * Поиск и удаление неиспользуемых файлов-меток для мьютексов,
     * c неиспользуемыми именами или заблокированными ранее максимального времени блокировки.
     *
     */
    public function deleteRandomExpiredFile(): void
    {
        $files = glob($this->config->getStoragePath() . '/*' . $this->config->getFileExtension());
        if ($files) {
            shuffle($files);
            $files = array_slice($files, 3);
            foreach ($files as $file) {
                $this->deleteExpiredFile($file);
            }
        }
    }

    /**
     * Deleting a specific tag file that has expired.
     *
     * Удаление конкретного файла-метки у которого истёк срок блокировки.
     *
     * @param string $file - путь до удаляемого файла.
     *                     - path to the file to be deleted.
     */
    public function deleteExpiredFile(string $file): void
    {
        if (file_exists($file)) {
            $lockdata = $this->getFileContent($file);
            if ($lockdata) {
                $lockdata = $this->stringToTagData($lockdata);
                if ($lockdata) {
                    if ($lockdata->getRevisionTime() < time() &&
                        $lockdata->getRevisionTime() - $lockdata->getUnlockSeconds() < time() - $this->config->getMaxLockTime()
                    ) {
                        $this->deleteFile($file);
                    }
                }
            } else {
                $filemtime = filemtime($file);
                if (time() - $filemtime > $this->config->getMaxLockTime()) {
                    $this->deleteFile($file);
                }
            }
        }
    }

    /**
     * Standardized file deletion.
     * 
     * Стандартизированное удаление файла.
     *
     * @param string $file - path to the file.
     *                     - путь до файла.
     * @return bool
     */
    public function deleteFile(string $file): bool
    {
        return @unlink($file);
    }

    /**
     * Returns the text content of the specified file, or null.
     *
     * Возвращает текстовое содержимое указанного файла или null.
     *
     * @param string $file - path to the file.
     *                     - путь до файла.
     * @return null
     */
    public function getFileContent(string $file): ?string
    {
        $content = @file($file, FILE_IGNORE_NEW_LINES)[0] ?? '';
        return !empty($content) ? $content : null;
    }

}

