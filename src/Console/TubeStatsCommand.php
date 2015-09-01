<?php

namespace Pvm\ArtisanBeans\Console;

use Illuminate\Support\Str;
use Pheanstalk\Exception\ServerException;

class TubeStatsCommand extends BaseCommand
{
    protected $commandName = 'tube';

    protected $commandArguments = '
        {tube? : Tube name}
    ';

    protected $commandOptions = '';

    protected $description = 'Show tube statistics';

    public function handle()
    {
        $this->parseArguments();

        if ($this->argument('tube')) {
            return $this->renderTubeStats($this->argument('tube'));
        }

        return $this->renderAllStats();
    }

    /**
     * @throws ServerException
     * @throws \Exception
     */
    protected function renderAllStats()
    {
        foreach ($this->getPheanstalk()->listTubes() as $tube) {
            $stats = $this->getTubeStats($tube);
            $data[] = [
                $stats['name'],
                $stats['current-jobs-buried'],
                $stats['current-jobs-delayed'],
                $stats['current-jobs-ready'],
                $stats['current-jobs-reserved'],
                $stats['current-jobs-urgent'],
                $stats['current-waiting'],
                $stats['total-jobs'],
            ];
        }

        $this->table([
            'Tube',
            'Buried',
            'Delayed',
            'Ready',
            'Reserved',
            'Urgent',
            'Waiting',
            'Total',
        ], $data);
    }

    /**
     * @param $tube
     *
     * @throws ServerException
     * @throws \Exception
     */
    protected function renderTubeStats($tube)
    {
        $stats = (array) $this->getTubeStats($tube);

        $this->table(['Property', 'Value'], $this->transformForTable($stats));
    }

    /**
     * @param $tube
     *
     * @throws ServerException
     * @throws \Exception
     *
     * @return object|\Pheanstalk\Response
     */
    protected function getTubeStats($tube)
    {
        try {
            $stats = $this->getPheanstalk()->statsTube($tube);
        } catch (ServerException $e) {
            if (Str::contains($e->getMessage(), 'NOT_FOUND')) {
                throw new \RuntimeException("Tube '$tube' doesn't exist.");
            }

            throw $e;
        }

        return $stats;
    }
}
