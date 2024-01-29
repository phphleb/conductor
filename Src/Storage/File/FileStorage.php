<?php
/**
 * A handler for requests to the file store of mutexes.
 *
 * Обработчик запросов в файловое хранилище мьютексов.
 */

namespace Phphleb\Conductor\Src\Storage\File;

use Phphleb\Conductor\Src\Scheme\{BaseConfigInterface, FileConfigInterface, StorageInterface};
use Exception;
use Phphleb\Conductor\Src\Storage\BaseStorage;
use Throwable;

class FileStorage extends BaseStorage implements StorageInterface
{
    protected FileConfigInterface $config;

    protected string $mutexName;

    protected string $mutexId;

    protected $fp = null;

    protected string $processHash;

    protected TagFileManager $tagManager;

    protected int $unlockSeconds = 0;

    protected int $revisionTime = 0;

    public function __construct(string $mutexName, BaseConfigInterface $config)
    {
        $this->mutexName = $mutexName;
        $this->mutexId = $this->generateIdFromName($mutexName);
        $this->config = $config;
        $this->processHash = \microtime(true) . '-' . \rand();
        $this->tagManager = new TagFileManager($config);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getConfig(): BaseConfigInterface
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function lockTag(int $unlockSeconds, int $revisionTime): bool
    {
        $this->unlockSeconds = $unlockSeconds;

        $this->revisionTime = $revisionTime;

        $this->prepareFileResources();

        $this->fp = \fopen($this->getFilePath(), "c+");

        if ($this->fp === false) {
            return false;
        }

        \flock($this->fp, LOCK_EX);

        return $this->createFile();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function checkLockedTagExists(): bool
    {
        $content = $this->tagManager->getFileContent($this->getFilePath());
        if ($content) {
            $tag = $this->tagManager->stringToTagData($content);
            $hash = $tag->getHash();
            unset($content, $tag);
            
            return $hash === $this->processHash;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function checkTagExists(): bool
    {
        $content = $this->tagManager->getFileContent($this->getFilePath());
        if ($content) {
            $tag = $this->tagManager->stringToTagData($content);
            $revisionTime = $tag->getRevisionTime();
            unset($content, $tag);
            
            return $revisionTime >= \time();
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function unlockTag(): bool
    {
        $this->unblockFp();
        return $this->deleteThisTagIfNotIntended();
    }

    protected function unblockFp(): bool
    {
        if (isset($this->fp, $this->fp) && $this->fp) {
            @\ftruncate($this->fp, 0);
            @\flock($this->fp, LOCK_UN);
            @\fclose($this->fp);
            $this->fp = null;
        }
        return false;
    }

    protected function deleteThisTagIfNotIntended(): bool
    {
        $file = $this->getFilePath();
        if (\file_exists($file)) {
            $tag = $this->tagManager->getTagData($file);
            if (!$tag || $tag->getHash() === $this->processHash) {
                return $this->tagManager->deleteFile($file);
            }
        }
        return true;
    }

    protected function createFile(): bool
    {
        try {
            if (\ftruncate($this->fp, 0) === false) {
                return $this->unblockFp();
            }
            $content = $this->tagManager->tagToStringData(
                $this->tagManager->valuesToTagData(
                    $this->revisionTime,
                    $this->unlockSeconds,
                    $this->processHash,
                    $this->mutexName
                )
            );
            if (\fputs($this->fp, $content) === false) {
                return $this->unblockFp();
            }
            if (\fflush($this->fp) === false) {
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
        $result = \is_dir($directory);
        if (!$result) {
            \mkdir($directory, 0775, true);
        }
        if (!\is_writable($directory)) {
            throw new Exception("The directory $directory is not available for writing files.");
        }
        return $result;
    }

    protected function getFilePath(): string
    {
        return $this->config->getStoragePath() . DIRECTORY_SEPARATOR . $this->mutexId . $this->config->getFileExtension();
    }

    /**
     * @throws Exception
     */
    protected function prepareFileResources(): void
    {
        if ($this->checkAndCreateDirectory()) {
            if (\rand(0, 5) === 1) {
                $this->tagManager->deleteRandomExpiredFile();
            }
        }
    }

    protected function generateIdFromName(string $name): string
    {
        return \sha1($name);
    }

}

