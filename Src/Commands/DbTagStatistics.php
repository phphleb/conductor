<?php
declare(strict_types=1);

/**
 * Class for creating a console command for mutex statistics from a database (for the HLEB framework).
 *
 * Класс для создания консольной команды по статистике мьютексов из БД (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Commands;


use Phphleb\Conductor\Src\Config\DbConfig;
use Phphleb\Conductor\Src\Scheme\DbConfigInterface;
use Phphleb\Conductor\Src\Storage\DB\TagDbManager;
use Phphleb\Conductor\Src\Tags\Tag;

class DbTagStatistics
{
    use BaseStatisticsTrait;

    private ?DbConfigInterface $config = null;

    /**
     * Возвращает информацию по активным мьютексам или отдельному мьютексу.
     *
     * Returns information on active mutexes or an individual mutex.
     *
     * @param string $name - custom mutex name.
     *                     - пользовательское название мьютекса.
     * @return string|null
     */
    public function execute(string $name = ''): ?string
    {
        $this->config = new DbConfig();
        if ($name) {
            $tag = (new TagDbManager($this->config))->getTagByName($name);
            if ($tag) {
                return $this->getTagInfo($tag);
            } else {
                return 'No active mutex with the same name was found.' . PHP_EOL;
            }

        } else {
            $tags = (new TagDbManager($this->config))->getAllTags();
            if ($tags) {
                $list = [];
                foreach ($tags as $tag) {
                    $list[] = $this->getTagInfo($tag);
                }
                return implode($list);
            } else {
                return 'No active mutexes found.' . PHP_EOL;
            }
        }

        return null;
    }

}

