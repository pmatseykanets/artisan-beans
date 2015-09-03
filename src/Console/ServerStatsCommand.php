<?php

namespace Pvm\ArtisanBeans\Console;

class ServerStatsCommand extends BaseCommand
{
    protected $commandName = 'server';

    protected $commandArguments = '
        {key? : Key name}
    ';

    protected $description = 'Show server statistics';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->parseArguments();

        return $this->renderStats($this->argument('key'));
    }

    /**
     * Displays server statistics
     *
     * @param $key
     */
    protected function renderStats($key)
    {
        $this->table(['Key', 'Value'], $this->transformForTable($this->getServerStats($key)));
    }
}
