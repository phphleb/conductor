<?php
declare(strict_types=1);

/**
 * Base class for configuring mutexes from the database (for the HLEB framework).
 *
 * Базовый класс конфигурации мьютексов из БД (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Config;


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
        if (!defined('HLEB_PARAMETERS_FOR_DB')) {
            $configDir = defined('HLEB_SEARCH_DBASE_CONFIG_FILE') ?
                HLEB_SEARCH_DBASE_CONFIG_FILE :
                HLEB_GLOBAL_DIRECTORY . '/database';

            $path = $configDir . '/dbase.config.php';
            if (!file_exists($path)) {
                $path = $configDir . '/default.dbase.config.php';
            }
            require $path;
        }
        $config = HLEB_PARAMETERS_FOR_DB[defined('HLEB_MUTEX_TYPE_DB') ? HLEB_MUTEX_TYPE_DB : HLEB_TYPE_DB];

        $this->userName = $config["user"] ?? '';
        $this->password = $config["pass"] ?? $config["password"] ?? '';

        $this->options = $config;

        if(!class_exists("\PDO")) {
            throw new \Exception('PHP extension `ext-pdo` must be installed');
        }
    }

    /**
     * Returns the maximum number of seconds that a lock can be held.
     *
     * Возвращает максимальное количество секунд на которое может быть произведена блокировка.
     *
     * @return int
     */
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
    public function getParams(): array
    {
        return $this->options;
    }

    public function getMutexTableName(): string
    {
        return self::MUTEX_TABLE_NAME;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}


