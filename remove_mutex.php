<?php
/**
 * Removing the auxiliary functionality of mutexes in the project on the HLEB framework.
 *
 * Удаление вспомогательного функционала мьютексов в проекте на фреймворке HLEB.
 */

    require __DIR__ . "/../updater/loader.php";
    require __DIR__ . "/../updater/FileRemover.php";

    $remover = new \Phphleb\Updater\FileRemover(__DIR__ . DIRECTORY_SEPARATOR);

    $remover->setSpecialNames('mutex', 'Mutex');

    $remover->run();


