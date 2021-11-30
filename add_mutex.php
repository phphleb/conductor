<?php
/**
 * Adding mutex functionality to a project based on the HLEB framework.
 * 
 * Добавление функционала мьютексов в проект на фреймворке HLEB.
 */

    require __DIR__ . "/../updater/loader.php";
    require __DIR__ . "/../updater/FileUploader.php";

    $uploader = new \Phphleb\Updater\FileUploader(__DIR__ . DIRECTORY_SEPARATOR . "files");

    $uploader->setDesign(['base']);

    $uploader->setPluginNamespace(__DIR__, 'Mutex');

    $uploader->setSpecialNames('mutex', 'Mutex');

    $uploader->run();


