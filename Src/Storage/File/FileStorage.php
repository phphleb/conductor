<?php
declare(strict_types=1);

/**
 * A handler for requests to the file store of mutexes.
 *
 * Обработчик запросов в файловое хранилище мьютексов.
 */

namespace Phphleb\Conductor\Src\Storage\File;

use Phphleb\Conductor\Src\Scheme\{BaseConfigInterface, FileConfigInterface, StorageInterface};
use Phphleb\Conductor\Src\Storage\BaseStorage;

class FileStorage extends BaseStorage implements StorageInterface
{
    protected FileConfigInterface $config;

    protected string $mutexName;

    protected string $mutexId;

    protected $fp = null;

    protected string $processHash;

    protected static TagFileManager $tagManager;

    protected int $unlockSeconds = 0;

    protected int $revisionTime = 0;

    public function __construct(string $mutexName, BaseConfigInterface $config)
    {
        $this->mutexName = $mutexName;
        $this->mutexId = $this->generateIdFromName($mutexName);
        $this->config = $config;
        $this->processHash = microtime(true) . '-' . rand();
        self::$tagManager = new TagFileManager($config);
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): BaseConfigInterface
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function lockTag(int $unlockSeconds, int $revisionTime): bool
    {
        $this->unlockSeconds = $unlockSeconds;

        $this->revisionTime = $revisionTime;

        $this->prepareFileResources();

        $this->fp = fopen($this->getFilePath(), "c+");

        if ($this->fp === false) {
            return false;
        }

        flock($this->fp, LOCK_EX);

        return $this->createFile();
    }

    /**
     * @inheritDoc
     */
    public function checkLockedTagExists(): bool
    {
        $content = self::$tagManager->getFileContent($this->config->getStoragePath());
        if ($content) {
            $tag = self::$tagManager->stringToTagData($content);
            $hash = $tag->getHash();
            unset($content, $tag);
            
            return $hash === $this->processHash;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function checkTagExists(): bool
    {
        $content = self::$tagManager->getFileContent($this->config->getStoragePath());
        if ($content) {
            $tag = self::$tagManager->stringToTagData($content);
            $revisionTime = $tag->getRevisionTime();
            unset($content, $tag);
            
            return $revisionTime >= time();
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function unlockTag(): bool
    {
        $this->unblockFp();
        return $this->deleteThisTagIfNotIntended();
    }

    protected function unblockFp(): bool
    {
        if (isset($this->fp, $this->fp) && $this->fp) {
            @ftruncate($this->fp, 0);
            @flock($this->fp, LOCK_UN);
            @fclose($this->fp);
            $this->fp = null;
        }
        return false;
    }

    protected function deleteThisTagIfNotIntended(): bool
    {
        $file = $this->getFilePath();
        if (file_exists($file)) {
            $tag = self::$tagManager->getTagData($file);
            if (!$tag || $tag->getHash() === $this->processHash) {
                return self::$tagManager->deleteFile($file);
            }
        }
        return true;
    }

    protected function createFile(): bool
    {
        try {
            if (ftruncate($this->fp, 0) === false) {
                return $this->unblockFp();
            }
            $content = self::$tagManager->tagToStringData(
                self::$tagManager->valuesToTagData(
                    $this->revisionTime,
                    $this->unlockSeconds,
                    $this->processHash,
                    $this->mutexName
                )
            );
            if (fputs($this->fp, $content) === false) {
                return $this->unblockFp();
            }
            if (fflush($this->fp) === false) {
                return $this->unblockFp();
            }
        } catch (Throwable $e) {
            return $this->unblockFp();
        }
        return true;
    }

    protected function checkAndCreateDirectory(): bool
    {
        $directory = $this->config->getStoragePath();
        $result = is_dir($directory);
        if (!$result) {
            mkdir($directory, 0775, true);
        }
        if (!is_writable($directory)) {
            throw new Exception("The directory $directory is not available for writing files.");
        }
        return $result;
    }

    protected function getFilePath(): string
    {
        return $this->config->getStoragePath() . DIRECTORY_SEPARATOR . $this->mutexId . $this->config->getFileExtension();
    }

    protected function prepareFileResources(): void
    {
        if ($this->checkAndCreateDirectory()) {
            if (rand(0, 5) === 1) {
                self::$tagManager->deleteRandomExpiredFile($this->config);
            }
        }
    }

    protected function generateIdFromName(string $name): string
    {
        return sha1($name);
    }


}

