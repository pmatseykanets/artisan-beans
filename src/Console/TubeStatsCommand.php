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

    protected $tubeStatsHeaders = [
        'Tube', 'Buried', 'Delayed', 'Ready', 'Reserved', 'Urgent', 'Waiting', 'Total',
    ];

    /**
     * {@inheritdoc}
     */
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
        foreach ($this->getTubes() as $tube) {
            $data[] = $this->transformTubeStatsForTable($this->getTubeStats($tube));
        }

        $this->table($this->tubeStatsHeaders, $data);
    }

    /**
     * @param $tube
     *
     * @throws ServerException
     * @throws \Exception
     */
    protected function renderTubeStats($tube)
    {
        $this->table(['Property', 'Value'], $this->transformForTable($this->getTubeStats($tube)));
    }

    /**
     * @param $stats
     * @return array
     */
    protected function transformTubeStatsForTable($stats)
    {
        return [
            $stats['name'].($stats['pause-time-left'] ? " (paused {$stats['pause-time-left']})" : ''),
            $stats['current-jobs-buried'],
            $stats['current-jobs-delayed'],
            $stats['current-jobs-ready'],
            $stats['current-jobs-reserved'],
            $stats['current-jobs-urgent'],
            $stats['current-waiting'],
            $stats['total-jobs'],
        ];
    }
}
