<?php

namespace Pvm\ArtisanBeans\Console;

class UnpauseTubeCommand extends PauseTubeCommand
{
    protected $commandName = 'unpause';

    protected $commandArguments = '
        {tube : Tube name}
    ';

    protected $description = 'Upause the tube';

    protected $delay = 0;

    /**
     * {$@inheritdoc}.
     */
    protected function getSuccessMessage($tube)
    {
        return "Tube '$tube' has been unpaused.";
    }

    /**
     *{@inheritdoc}.
     */
    protected function parseCommandArguments()
    {
        // Override parent's parseCommandArguments
        // since we use a predefined value for $this->delay
    }
}
