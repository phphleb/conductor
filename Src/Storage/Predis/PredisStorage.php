<?php
/**
 * Predis-based mutex store request handler.
 *
 * Обработчик запросов в хранилище мьютексов на основе Predis.
 */

namespace Phphleb\Conductor\Src\Storage\Predis;

use Phphleb\Conductor\Src\Scheme\BaseConfigInterface;
use Phphleb\Conductor\Src\Storage\BaseStorage;
use Phphleb\Conductor\Src\Scheme\PredisConfigInterface;
use Phphleb\Conductor\Src\Scheme\StorageInterface;

class PredisStorage extends BaseStorage implements StorageInterface
{
    protected string $mutexName;

    protected string $mutexId;

    protected PredisConfigInterface $config;

    protected ?TagPredisManager $tagManager = null;

    protected int $unlockSeconds = 0;

    protected int $revisionTime = 0;

    protected string $processHash;

    public function __construct(string $mutexName, BaseConfigInterface $config)
    {
        $this->mutexName = $mutexName;
        $this->mutexId = $this->generateIdFromName($mutexName);
        $this->config = $config;
        $this->processHash = \microtime(true) . '-' . \rand();

        if ($this->tagManager === null) {
            $this->tagManager = new TagPredisManager($config);
        }
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

        $this->preparePredisResources();

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

    /**
     * @inheritDoc
     */
    protected function generateIdFromName(string $name): string
    {
        return \sha1($name);
    }

    protected function preparePredisResources(): void
    {
        if (\rand(0, 20) === 1) {
            $this->tagManager->deleteExpiredTags();
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

