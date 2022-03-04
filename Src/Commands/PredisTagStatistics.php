<?php
declare(strict_types=1);

/**
 * Class for creating a console command for mutex statistics from a Redis(Predis) (for the HLEB framework).
 *
 * Класс для создания консольной команды по статистике мьютексов из Redis(Predis) (для фреймворка HLEB).
 */

namespace Phphleb\Conductor\Src\Commands;


use Phphleb\Conductor\Src\Config\PredisConfig;
use Phphleb\Conductor\Src\Scheme\PredisConfigInterface;
use Phphleb\Conductor\Src\Storage\Predis\TagPredisManager;
use Phphleb\Conductor\Src\Tags\Tag;

class PredisTagStatistics
{
    use BaseStatisticsTrait;

    private ?PredisConfigInterface $config = null;

    /**
     * Возвращает информацию по активным мьютексам или отдельному мьютексу.
     *
     * Returns information on active mutexes or an individual mutex.
     *
     * @param string $id - mutex ID.
     *                   - идентификатор мьютекса.
     * @return string|null
     */
    public function execute(string $id = ''): ?string
    {
        $this->config = new PredisConfig();
        if ($id) {
            $tag = (new TagPredisManager($this->config))->getTagData($id);
            if ($tag) {
                return $this->getTagInfo($tag);
            } else {
                return 'No active mutex with the same ID was found.' . PHP_EOL;
            }

        } else {
            $tags = (new TagPredisManager($this->config))->getAllTags();
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
    }

}

