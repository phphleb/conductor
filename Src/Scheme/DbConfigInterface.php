<?php

namespace Phphleb\Conductor\Src\Scheme;


interface DbConfigInterface
{
    public function getParams(): array;

    public function getMutexTableName(): string;

    public function getUserName(): string;

    public function getPassword(): string;

}

