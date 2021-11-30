<?php

namespace Phphleb\Conductor\Src\Scheme;


interface FileConfigInterface
{
    public function getStoragePath(): string;

    public function getFileExtension(): string;
}

