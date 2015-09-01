<?php

namespace Pvm\ArtisanBeans\Console;

class ServerStatsCommand extends BaseCommand
{
    protected $commandName = 'server';

    protected $commandArguments = '{key? : Key name}';

    protected $commandOptions = '';

    protected $description = 'Show server statistics';

    /**
     *
     */
    public function handle()
    {
        $this->parseArguments();

        return $this->renderStats($this->argument('key'));
    }

    /**
     * @param $key
     */
    protected function renderStats($key)
    {
        $this->table(['Key', 'Value'], $this->transformForTable($this->getStats($key)));
    }

    /**
     * @param string $pattern
     *
     * @return array
     */
    protected function getStats($pattern = '')
    {
        $stats = (array) $this->getPheanstalk()->stats();

        if (!empty($pattern)) {
            $stats = array_filter($stats, function ($key) use ($pattern) {
                return 1 === preg_match("/$pattern/i", $key);
            }, ARRAY_FILTER_USE_KEY);
        }

        ksort($stats);

        return $stats;
    }
}
