<?php
declare(strict_types=1);
/**
 * @author  Foma Tuturov <fomiash@yandex.ru>
 */

/**
 * Implementation of the mutex that stores the state in the Redis(Predis).
 * P.S. Queues where other processes are waiting to be unlocked are NON-SEQUENTIAL.
 *
 * Реализация мьютекса хранящего состояние в Redis(Predis).
 * P.S. Очереди, когда другие процессы ожидают разблокировки, являются НЕПОСЛЕДОВАТЕЛЬНЫМИ.
 */

namespace Phphleb\Conductor;

use Phphleb\Conductor\Src\Scheme\{OriginMutexInterface, BaseConfigInterface};
use Phphleb\Conductor\Src\{Config\PredisConfig, MutexDirector, OriginMutex};
use Phphleb\Conductor\Src\Storage\Predis\PredisStorage;


class PredisMutex extends MutexDirector
{
    protected static ?BaseConfigInterface $config = null;

    /**
     * @inheritDoc
     */
    public function __construct(?BaseConfigInterface $config = null)
    {
        if (is_null(self::$config)) {
            self::$config = is_null($config) ? new PredisConfig() : $config;
        }
    }

    /**
     * @inheritDoc
     */
    protected function createMutex(string $name): OriginMutexInterface
    {
        return new OriginMutex(new PredisStorage($name,  self::$config));
    }

}

