<?php
/**
 * Base class for configuring mutexes from the database (for the HLEB framework).
 *
 * Базовый класс конфигурации мьютексов из БД (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Config;

use Hleb\Static\Settings;
use Phphleb\Conductor\Src\Scheme\BaseConfigInterface;
use Phphleb\Conductor\Src\Scheme\DbConfigInterface;

class DbConfig implements DbConfigInterface, BaseConfigInterface
{
    protected const MAX_LOCK_TIME = 14400;

    protected const QUEUE_WAIT_INTERVAL = 300_000;

    protected const MUTEX_TABLE_NAME = 'mutex_auto_tags';

    protected array $options;

    protected string $userName;

    protected string $password;

    public function __construct()
    {
        $type = Settings::getParam('database', 'mutex.db.type');
        $list = Settings::getParam('database', 'db.settings.list');
        $config = $list[$type];
        $this->userName = $config["user"] ?? '';
        $this->password = $config["pass"] ?? '';
        $this->options = $config;

        if(!\class_exists("PDO")) {
            throw new \ErrorException('PHP extension `ext-pdo` must be installed');
        }
    }

    /**
     * Returns the maximum number of seconds that a lock can be held.
     *
     * Возвращает максимальное количество секунд на которое может быть произведена блокировка.
     *
     * @return int
     */
    #[\Override]
    public function getMaxLockTime(): int
    {
        return self::MAX_LOCK_TIME;
    }

    /**
     * Returns how long a process waits in the queue between checking a lock.
     * Value in microseconds.
     *
     * Возвращает интервал ожидания процесса в очереди между проверкой блокировки.
     * Значение в микросекундах.
     *
     * @return int
     */
    #[\Override]
    public function getQueueWaitIntervalInUs(): int
    {
        return self::QUEUE_WAIT_INTERVAL;
    }

    /**
     * Returns parameters for connection and other options.
     *
     * Возвращает параметры для соединения и других опций.
     *
     * @return array
     */
    #[\Override]
    public function getParams(): array
    {
        return $this->options;
    }

    #[\Override]
    public function getMutexTableName(): string
    {
        return self::MUTEX_TABLE_NAME;
    }

    #[\Override]
    public function getUserName(): string
    {
        return $this->userName;
    }

    #[\Override]
    public function getPassword(): string
    {
        return $this->password;
    }
}


