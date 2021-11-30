<?php

namespace Phphleb\Conductor\Src\Scheme;


interface BaseConfigInterface
{
    public function getMaxLockTime(): int;

    public function getQueueWaitIntervalInUs(): int;

}

