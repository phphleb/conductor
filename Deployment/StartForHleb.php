<?php

declare(strict_types=1);

namespace Phphleb\Conductor\Deployment;

use Hleb\Main\Console\Commands\Deployer\DeploymentLibInterface;
use JsonException;
use Phphleb\Updater\AddAction;
use Phphleb\Updater\RemoveAction;

class StartForHleb implements DeploymentLibInterface
{
    private bool $noInteraction = false;

    private bool $quiet = false;

    /**
     * @param array $config - configuration for deploying libraries,
     *                        sample in updater.json file.
     *                      - конфигурация для развертывания библиотек,
     *                        образец в файле updater.json.
     */
    #[\Override]
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function noInteraction(): void
    {
        $this->noInteraction = true;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function help(): string|false
    {
        return 'Performs deployment/rollback for the `conductor` component.';
    }

    /**
     * @inheritDoc
     *
     * @throws JsonException
     */
    #[\Override]
    public function add(): int
    {
        $action = new AddAction($this->config, $this->noInteraction, $this->quiet);
        return $action->run();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function remove(): int
    {
        return (new RemoveAction($this->config, $this->noInteraction, $this->quiet))->run();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function classmap(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function quiet(): void
    {
        $this->quiet = true;
    }
}