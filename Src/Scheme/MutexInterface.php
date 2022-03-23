<?php
/**
 * External mutex interface.
 */

namespace Phphleb\Conductor\Src\Scheme;

interface MutexInterface
{
    public function acquire(string $mutexName, ?int $unlockSeconds = null): bool;
    public function release(string $mutexName): bool;
    public function unlock(string $mutexName): bool;
    public function isIntercepted(string $mutexName): bool;
    public function isCompleted(string $mutexName): bool;
}

