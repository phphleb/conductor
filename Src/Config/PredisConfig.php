<?php
declare(strict_types=1);
/**
 * Base class for configuring mutexes from the Redis(Predis) (for the HLEB framework).
 *
 * Базовый класс конфигурации мьютексов из Redis(Predis)  (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Config;


use Phphleb\Conductor\Src\Scheme\BaseConfigInterface;
use Phphleb\Conductor\Src\Scheme\PredisConfigInterface;

class PredisConfig implements PredisConfigInterface, BaseConfigInterface
{
    protected const MAX_LOCK_TIME = 14400;

    protected const QUEUE_WAIT_INTERVAL = 100_000;

    protected const MUTEX_PREFIX = 'mutex_auto_tags';

    protected const DATABASE_DEFAULT_CONFIG_PATH = HLEB_GLOBAL_DIRECTORY . '/database/default.dbase.config.php';

    protected const DATABASE_CONFIG_PATH = HLEB_GLOBAL_DIRECTORY . '/database/dbase.config.php';

    protected array $parameters;

    protected array $options;

    public function __construct()
    {
        if (!defined('HLEB_PARAMETERS_FOR_DB')) {
            $path = self::DATABASE_CONFIG_PATH;
            if (!file_exists($path)) {
                $path = self::DATABASE_DEFAULT_CONFIG_PATH;
            }
            require $path;
        }
        $config = HLEB_PARAMETERS_FOR_DB[defined('HLEB_TYPE_REDIS') ? HLEB_TYPE_REDIS : (defined('HLEB_MUTEX_TYPE_REDIS') ? HLEB_MUTEX_TYPE_REDIS : HLEB_TYPE_DB)];

        $this->parameters = $config;

        $this->options = $config['options'] ?? [];

        unset($config['options']);

        if (!class_exists("\Predis\Client")) {
            throw new \Exception('The library Predis must be installed (https://github.com/predis/predis)');
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

    public function getMutexPrefix(): string
    {
        return self::MUTEX_PREFIX;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}


