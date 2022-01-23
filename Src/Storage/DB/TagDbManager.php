<?php
declare(strict_types=1);

/**
 * Class for manipulating mutex tags from the database.
 *
 * Класс для манипулирования метками мьютексов из базы данных.
 */

namespace Phphleb\Conductor\Src\Storage\DB;


use Phphleb\Conductor\Src\Scheme\DbConfigInterface;
use Phphleb\Conductor\Src\Tags\Tag;

class TagDbManager
{
    protected DbConfigInterface $config;

    protected ?string $dbType;

    protected \PDO $pdo;

    public function __construct(DbConfigInterface $config)
    {
        $this->config = $config;
        $this->pdo = $this->createConnection();
        $this->dbType = $this->selectDbType();
    }

    protected function createConnection(): \PDO
    {
        $params = $this->config->getParams();
        $opt = $params["options-list"] ?? [];
        $opt[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;
        $opt[\PDO::ATTR_DEFAULT_FETCH_MODE] = $params["default-mode"] ?? \PDO::FETCH_ASSOC;
        $opt[\PDO::ATTR_EMULATE_PREPARES] = $params["emulate-prepares"] ?? false;
        $user = $this->config->getUserName();
        $pass = $this->config->getPassword();
        $condition = [];
        foreach ($params as $key => $param) {
            if (is_numeric($key)) {
                $condition [] = preg_replace('/\s+/', '', $param);
            }
        }
        $connection = implode(";", $condition);

        return $this->pdo = new \PDO($connection, $user, $pass, $opt);
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    public function getTagData(string $tagId): ?Tag
    {
        $data = $this->pdo->query(
            "SELECT * FROM {$this->config->getMutexTableName()} WHERE tag = '{$tagId}' LIMIT 1"
        )->fetch();
        if ($data) {
            return $this->valuesToTagData(...$this->sortArrayFromData($data));
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
     * @param string $name       - custom mutex name.
     *                           - пользовательское название мьютекса.
     *
     * @return Tag
     */

    public function valuesToTagData(int $revisionTime, int $unlockSeconds, string $hash, string $name): Tag
    {
        return new Tag($revisionTime, $unlockSeconds, $hash, $name);
    }

    public function saveTag(string $tagId, Tag $tag): bool
    {
        if ($this->deleteTag($tagId)) {
            $this->pdo
                ->prepare(
                    "INSERT INTO {$this->config->getMutexTableName()} (tag, title, hash, unlock_seconds, revision_time, date_create) VALUES (?,?,?,?,?,NOW())"
                )->execute([
                    $tagId, $tag->getName(), $tag->getHash(), $tag->getUnlockSeconds(), $tag->getRevisionTime(),
                ]);
            return true;
        }
        return false;
    }

    public function getTagRevisionTime(string $tagId): ?int
    {
        try {
            return (int)$this->pdo
                ->query("SELECT revision_time FROM {$this->config->getMutexTableName()} WHERE tag = '{$tagId}' LIMIT 1 ")
                ->fetchColumn();

        } catch (\Throwable $e) {

            // If the table is not created.
        }
        return null;
    }

    public function getLockTagExists(string $tagId, string $hash): bool
    {
        try {
            return (bool)$this->pdo
                ->query("SELECT unlock_seconds FROM {$this->config->getMutexTableName()} WHERE tag = '{$tagId}' AND hash = '{$hash}' LIMIT 1 ")
                ->fetchColumn();

        } catch (\Throwable $e) {

            // If the table is not created.
        }
        return false;
    }

    public function deleteTag(string $tagId): bool
    {
        try {
            $this->pdo->exec("DELETE FROM {$this->config->getMutexTableName()} WHERE tag = '{$tagId}'");
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function deleteLockedTag(string $tagId, string $hash): bool
    {
        try {
            $this->pdo->exec("DELETE FROM {$this->config->getMutexTableName()} WHERE tag = '{$tagId}' AND hash = '{$hash}'");
        } catch (\Throwable $e) {
            return false;
        }
        return true;
    }

    public function deleteExpiredTags(): void
    {
        try {
            $this->pdo->exec("DELETE FROM {$this->config->getMutexTableName()} WHERE revision_time < '" . (time() - $this->config->getMaxLockTime()) . "'");
        } catch (\Throwable $e) {

            // If the table is not created.
        }
    }

    public function getTagByName(string $name): ?Tag
    {
        try {
            $data = $this->pdo->prepare("SELECT * FROM {$this->config->getMutexTableName()} WHERE title = ? LIMIT 1 ");
            $data->execute([$name]);
            if ($data) {
                return $this->valuesToTagData(...$this->sortArrayFromData($data->fetch()));
            }
        } catch (\Throwable $e) {

            // If the table is not created.
        }
        return null;
    }

    public function getAllTags(): array
    {
        $tags = [];
        try {
            $data = $this->pdo->query("SELECT * FROM {$this->config->getMutexTableName()} LIMIT 10000 ");
            foreach ($data as $row) {
                $tags[] = $this->valuesToTagData(...$this->sortArrayFromData($row));
            }
        } catch (\Throwable $e) {

            // If the table is not created.
        }
        return $tags;
    }

    public function checkAndcreateTable(): bool
    {
        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            try {
                $this->pdo->query("SELECT 1 FROM '{$this->config->getMutexTableName()}'")->fetch();
            } catch (\Throwable $e) {
                return (bool)$this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->config->getMutexTableName()} (
                    tag VARCHAR(50) NOT NULL,
                    title VARCHAR(250) NOT NULL,
                    hash VARCHAR(30) NOT NULL,
                    unlock_seconds INTEGER NOT NULL,
                    revision_time INTEGER NOT NULL,
                    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (tag),
                    UNIQUE (title)
        )");

            }
        } else {
            if (!(bool)$this->pdo->query("SHOW TABLES LIKE'{$this->config->getMutexTableName()}'")->fetch()) {
                return (bool)$this->pdo->exec("
                CREATE TABLE IF NOT EXISTS {$this->config->getMutexTableName()} (
                    tag VARCHAR(50) NOT NULL PRIMARY KEY,
                    title VARCHAR(250) NOT NULL UNIQUE KEY,
                    hash VARCHAR(30) NOT NULL,
                    unlock_seconds INT(6) NOT NULL,
                    revision_time INT(11) NOT NULL,
                    date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
            }
        }

        return false;
    }

    private function sortArrayFromData(array $data): array
    {
        return [
            (int)$data['revision_time'],
            (int)$data['unlock_seconds'],
            (string)$data['hash'],
            (string)$data['title']
        ];
    }

}


