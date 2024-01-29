<?php
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

    protected ?TagDbManager $tagManager = null;

    protected int $unlockSeconds = 0;

    protected int $revisionTime = 0;

    protected string $processHash;    
    
    public function __construct(string $mutexName, BaseConfigInterface $config)
    {
        $this->mutexName = $mutexName;
        $this->mutexId = $this->generateIdFromName($mutexName);
        $this->config = $config;
        $this->processHash = \microtime(true) . '-' . \rand();
        $this->tagManager = new TagDbManager($config);
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

        $this->prepareDbResources();

        return $this->createTag();
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function checkTagExists(): bool
    {
        $revisionTime = $this->tagManager->getTagRevisionTime($this->mutexId);
        if ($revisionTime) {
            return $revisionTime >= \time();
        }
        return false;
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function checkLockedTagExists(): bool
    {
        return $this->tagManager->getLockTagExists($this->mutexId, $this->processHash);
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function unlockTag(): bool
    {
        return $this->tagManager->deleteLockedTag($this->mutexId, $this->processHash);
    }
    
    protected function generateIdFromName(string $name): string
    {
        return \sha1($name);
    }
    
    protected function prepareDbResources(): void
    {
        if (!$this->tagManager->checkAndCreateTable()) {
            if (\rand(0, 5) === 1) {
                $this->tagManager->deleteExpiredTags();
            }
        }
    }

    protected function createTag(): bool
    {
        return $this->tagManager->saveTag(
            $this->mutexId,
            $this->tagManager->valuesToTagData(
                $this->revisionTime,
                $this->unlockSeconds,
                $this->processHash,
                $this->mutexName
            )
        );
    }

}

