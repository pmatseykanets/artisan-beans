<?php

namespace Pvm\ArtisanBeans\Console;

class UnpauseTubeCommand extends PauseTubeCommand
{
    protected $commandName = 'unpause';

    protected $commandArguments = '
        {tube : Tube name}
    ';

    protected $description = 'Upause the tube';

    /**
     *{@inheritdoc}.
     */
    protected function parseCommandArguments()
    {
    }

    /**
     * {$@inheritdoc}.
     */
    protected function getSuccessMessage($tube)
    {
        return "Tube '$tube' has been unpaused.";
    }

    /**
     * {$@inheritdoc}.
     */
    protected function getDelay()
    {
        return 0;
    }
}
