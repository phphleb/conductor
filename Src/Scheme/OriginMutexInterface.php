<?php
/**
 * Internal mutex interface.
 */

namespace Phphleb\Conductor\Src\Scheme;

interface OriginMutexInterface
{
    public function acquire(?int $unlockSeconds = null): bool;
    public function release(): bool;
    public function unlock(): bool;
    public function isIntercepted(): bool;
    public function isCompleted(): bool;
    public function getStatus(): ?bool;
}

