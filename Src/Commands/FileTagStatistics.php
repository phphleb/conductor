<?php
declare(strict_types=1);

/**
 * Class for creating a console command for file mutex statistics (for the HLEB framework).
 *
 * Класс для создания консольной команды по статистике файловых мьютексов (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Commands;


use Phphleb\Conductor\Src\Config\FileConfig;
use Phphleb\Conductor\Src\Scheme\FileConfigInterface;
use Phphleb\Conductor\Src\Tags\Tag;
use Phphleb\Conductor\Src\Storage\File\TagFileManager;

class FileTagStatistics
{
    use BaseStatisticsTrait;

    private ?FileConfigInterface $config = null;

    private ?TagFileManager $tagManager = null;

    /**
     * Returns information on active mutexes or an individual mutex.
     *
     * Возвращает информацию по активным мьютексам или отдельному мьютексу.
     *
     * @param string $name - mutex name, file name or file hash.
     *                     - название мьютекса, имя файла или хеш файла.
     * @return string|null
     */
    public function execute(string $name = ''): ?string
    {
        $this->config = new FileConfig();

        $this->tagManager = (new TagFileManager($this->config));

        if ($name) {
            $file = $this->searchFile($name);
            if ($file) {
                echo $this->getFileInfo($file);
            } else {
                echo 'No active mutex with the same name was found.' . PHP_EOL;
            }

        } else {
            $files = \glob($this->config->getStoragePath() . '/*' . $this->config->getFileExtension());

            if ($files) {
                $list = [];
                foreach ($files as $file) {
                    if (\file_exists($file)) {
                        $list[] = $this->getFileInfo($file);
                    }
                }
                return \implode($list);

            } else {
                echo 'No active mutexes found.' . PHP_EOL;
            }
        }

        return null;
    }

    private function getFileInfo(string $file): string
    {
        $tag = $this->tagManager->getTagData($file);

        return $this->getTagInfo($tag);
    }

    private function searchFile(string $name): ?string
    {
        $list = [
            \sha1($name) . '.txt',
            $name . '.txt',
            $name
        ];
        foreach ($list as $name) {
            $file = $this->config->getStoragePath() . '/' . $name;
            if (\file_exists($file)) {
                return $file;
            }
        }

        return null;
    }
}

