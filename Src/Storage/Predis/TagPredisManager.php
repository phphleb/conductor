<?php
declare(strict_types=1);
/**
 * A class for manipulating mutex tags from Redis(Predis).
 *
 * Класс для манипулирования метками мьютексов из Redis(Predis).
 */

namespace Phphleb\Conductor\Src\Storage\Predis;


use Phphleb\Conductor\Src\Scheme\PredisConfigInterface;
use Phphleb\Conductor\Src\Tags\Tag;
use Predis\Client as PredisClient;

class TagPredisManager
{
    protected const DELIMITER = ':';

    protected PredisConfigInterface $config;

    protected PredisClient $client;

    public function __construct(PredisConfigInterface $config)
    {
        $this->config = $config;
        $this->client = $this->createConnection();
    }

    public function getTagData(string $tagId): ?Tag
    {
        $data = $this->client->get($this->createKey($tagId));
        if ($data) {
            return $this->valuesToTagData(...$this->sortStringFromData($data));
        }
        return null;
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
     * @param string $name       - custom mutex name.s
     *                           - пользовательское название мьютекса.
     *
     * @return Tag
     */

    public function valuesToTagData(int $revisionTime, int $unlockSeconds, string $hash, string $name): Tag
    {
        return new Tag($revisionTime, $unlockSeconds, $hash, $name);
    }

    public function stringToTagData(string $data): ?Tag
    {
        try {
            return $this->valuesToTagData(...$this->sortStringFromData($data));
        } catch (\Throwable $e) {

        }
        return null;
    }

    public function saveTag(string $tagId, Tag $tag): bool
    {
        try {
            $data = implode(self::DELIMITER, [$tagId, $tag->getRevisionTime(), $tag->getUnlockSeconds(), $tag->getHash(), $tag->getName()]);
            $this->client->set($this->createKey($tagId), $data);
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function deleteTag(string $tagId): void
    {
        $this->client->del($this->createKey($tagId));
    }

    public function deleteExpiredTags(): void
    {
        try {
            $keys = $this->client->keys('*' . $this->config->getMutexPrefix() . '*');
            shuffle($keys);
            $keys = array_slice($keys, 5);
            foreach ($keys as $key) {
                $tag = $this->stringToTagData($this->client->get($key));
                if (empty($tag) || ($tag->getRevisionTime() < time() &&
                        $tag->getRevisionTime() - $tag->getUnlockSeconds() < time() - $this->config->getMaxLockTime())) {
                    $this->client->del($key);
                }
            }
        } catch (\Throwable $e) {

        }
    }


    public function getTagRevisionTime(string $tagId): ?int
    {
        try {
            $tag = $this->getTagData($tagId);
            if ($tag) {
                return $tag->getRevisionTime();
            }
        } catch (\Throwable $e) {

        }
        return null;
    }

    public function getLockTagExists(string $tagId, string $hash): bool
    {
        try {
            $tag = $this->getTagData($tagId);
            if ($tag) {
                return $tag->getHash() === $hash;
            }
        } catch (\Throwable $e) {

        }
        return false;
    }

    public function deleteLockedTag(string $tagId, string $hash): bool
    {
        try {
            $tag = $this->getTagData($tagId);
            if ($tag && $tag->getHash() === $hash) {
                $this->deleteTag($tagId);
            }
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function getAllTags(): array
    {
        $tags = [];
        $keys = $this->client->keys('*' . $this->config->getMutexPrefix() . '*');
        foreach ($keys as $key) {
            $tag = $this->stringToTagData($this->client->get($key));
            if ($tag) {
                $tags[] = $tag;
            }
        }
        return $tags;
    }

    protected function createConnection(): PredisClient
    {
        return new PredisClient($this->config->getParameters(), $this->config->getOptions());
    }

    private function sortStringFromData(string $data): array
    {
        $list = explode(self::DELIMITER, $data);
        $tagId = (string)array_shift($list);
        $revisionTime = (int)array_shift($list);
        $unlockSeconds = (int)array_shift($list);
        $hash = (string)array_shift($list);
        $name = implode(self::DELIMITER, $list);

        return [$revisionTime, $unlockSeconds, $hash, $name];
    }

    private function createKey(string $tagId): string
    {
        return $this->config->getMutexPrefix() . "-" . $tagId;
    }

}


