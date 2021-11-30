<?php
/**
 * The class allows you to create a file-label object.
 *
 * Класс позволяет создать объект файла-метки.
 */

namespace Phphleb\Conductor\Src\Tags;


class Tag
{
    private int $revisionTime;

    private int $unlockSeconds;

    private string $hash;

    private string $name;

    /**
     * @param int $revisionTime  - the Unix system timestamp when the lock was completed.
     *                           - метка системного времени Unix завершения блокировки.
     *
     * @param int $unlockSeconds - the number of seconds to block.
     *                           - количество секунд блокировки.
     *
     * @param string $hash       - identifier of the current process.
     *                           - идентификатор текущего процесса.
     *
     * @param string $name       - custom mutex name.
     *                           - пользовательское название мьютекса.
     */
    public function __construct(int $revisionTime, int $unlockSeconds, string $hash, string $name)
    {
        $this->revisionTime = $revisionTime;
        $this->unlockSeconds = $unlockSeconds;
        $this->hash = $hash;
        $this->name = $name;

    }

    /**
     * @return int
     */
    public function getRevisionTime(): int
    {
        return $this->revisionTime;
    }

    /**
     * @return int
     */
    public function getUnlockSeconds(): int
    {
        return $this->unlockSeconds;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

}

