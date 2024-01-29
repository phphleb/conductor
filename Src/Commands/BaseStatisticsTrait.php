<?php
declare(strict_types=1);

/**
 * This trait allows you to display standardized information in statistics.
 *
 * Этот трейт позволяет выводить стандартизированную информацию в статистике.
 */

namespace Phphleb\Conductor\Src\Commands;

use Phphleb\Conductor\Src\Tags\Tag;

trait BaseStatisticsTrait
{
    private function getTagInfo(Tag $tag): string
    {
        $name = $tag->getName();
        $tagId = \sha1($name);
        $startTime = $tag->getRevisionTime() - $tag->getUnlockSeconds();
        $date = \date("d-m-Y H:i:s", $startTime) . ' - ' . \date("d-m-Y H:i:s", $tag->getRevisionTime());
        $expiredTime = $tag->getUnlockSeconds();
        $passedTime = \time() - $startTime;
        $lock = $passedTime <= $expiredTime ? 'lock' : 'EXPIRED';

        return $tagId . " | $date | $passedTime/$expiredTime sec. $lock  [$name]" . PHP_EOL;
    }
}

