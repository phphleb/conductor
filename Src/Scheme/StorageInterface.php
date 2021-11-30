<?php


namespace Phphleb\Conductor\Src\Scheme;


interface StorageInterface
{
    public function __construct(string $mutexId, BaseConfigInterface $config);

    public function getConfig(): BaseConfigInterface;

    public function lockTag(int $unlockSeconds, int $revisionTime): bool;

    public function checkTagExists(): bool;

    public function checkLockedTagExists(): bool;

    public function unlockTag(): bool;

}

