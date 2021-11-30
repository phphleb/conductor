<?php
declare(strict_types=1);
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

    protected static ?TagPredisManager $tagManager = null;

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
            self::$tagManager = new TagPredisManager($config);
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

        $this->preparePredisResources();

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

    protected function preparePredisResources(): void
    {
        if (rand(0, 20) === 1) {
            self::$tagManager->deleteExpiredTags();
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

