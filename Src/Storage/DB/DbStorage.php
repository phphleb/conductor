<?php
declare(strict_types=1);

/**
 * A query processor for a database-based mutex store.
 *
 * Обработчик запросов в хранилище мьютексов на основе БД.
 */

namespace Phphleb\Conductor\Src\Storage\DB;

use Phphleb\Conductor\Src\Scheme\BaseConfigInterface;
use Phphleb\Conductor\Src\Storage\BaseStorage;
use Phphleb\Conductor\Src\Scheme\DbConfigInterface;
use Phphleb\Conductor\Src\Scheme\StorageInterface;

class DbStorage extends BaseStorage implements StorageInterface
{
    protected string $mutexName;

    protected string $mutexId;

    protected DbConfigInterface $config;

    protected static ?\PDO $connection = null;

    protected static ?TagDbManager $tagManager = null;

    protected int $unlockSeconds = 0;

    protected int $revisionTime = 0;

    protected string $processHash;    
    
    public function __construct(string $mutexName, BaseConfigInterface $config)
    {
        $this->mutexName = $mutexName;
        $this->mutexId = $this->generateIdFromName($mutexName);
        $this->config = $config;
        $this->processHash = microtime(true) . '-' . rand();

        if (is_null(self::$tagManager)) {
            self::$tagManager = new TagDbManager($config);
        }
        if (is_null(self::$connection)) {
            self::$connection = self::$tagManager->getConnection();
        }
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

        return $this->createTag();
    }
    
    /**
     * @inheritDoc
     */
    public function checkTagExists(): bool
    {
        $revisionTime = self::$tagManager->getTagRevisionTime($this->mutexId);
        if ($revisionTime) {
            return $revisionTime >= time();
        }
        return false;
    }
    
    /**
     * @inheritDoc
     */   
    public function checkLockedTagExists(): bool
    {
        return self::$tagManager->getLockTagExists($this->mutexId, $this->processHash);
    }
    
    /**
     * @inheritDoc
     */
    public function unlockTag(): bool
    {
        return self::$tagManager->deleteLockedTag($this->mutexId, $this->processHash);
    }
    
    /**
     * @inheritDoc
     */
    protected function generateIdFromName(string $name): string
    {
        return sha1($name);
    }
    
    protected function prepareFileResources(): void
    {
        if (!self::$tagManager->checkAndcreateTable()) {
            if (rand(0, 5) === 1) {
                self::$tagManager->deleteExpiredTags();
            }
        }
    }

    protected function createTag(): bool
    {
        return self::$tagManager->saveTag(
            $this->mutexId,
            self::$tagManager->valuesToTagData(
                $this->revisionTime,
                $this->unlockSeconds,
                $this->processHash,
                $this->mutexName
            )
        );
    }

}

