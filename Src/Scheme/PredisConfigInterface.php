<?php

namespace Phphleb\Conductor\Src\Scheme;


interface PredisConfigInterface
{
    public function getMutexPrefix(): string;

    public function getParameters(): array;

    public function getOptions(): array;

}

