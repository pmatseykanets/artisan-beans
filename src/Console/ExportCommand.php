<?php

namespace Pvm\ArtisanBeans\Console;

class ExportCommand extends BaseCommand
{
    protected $commandName = 'export';

    protected $commandArguments = '
        {path : Path to export jobs}
        {state=ready : State [ready|delayed|buried]}
        {tube? : Tube name}
        {count=1 : Number of jobs to export}
    ';

    protected $description = 'Export jobs';

    protected $path;

    protected $state;

    protected $count = 1;

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->parseArguments();

        $tube = $this->argument('tube') ?: $this->defaultTube;

        $exported = 0;
        while ($job = $this->peekJob($tube, $this->state)) {
            if ($this->count > 0 && $exported >= $this->count) {
                break;
            }

            $this->exportJob($job);

            $this->deleteJob($job);

            $exported++;
        }

        if (0 == $exported) {
            $this->renderJobNotFoundMessage($tube, $this->state);
        } else {
            $this->comment("Exported $exported jobs from '$tube' tube in '$this->state' state.");
        }
    }

    /**
     * Exports job to a file.
     *
     * @param $job
     */
    protected function exportJob($job)
    {
        $stats = $this->getJobStats($job);

        $contents = $this->renderForExport($job, $stats);

        $filename = trim($this->path, '/').'/'.$this->buildJobFileName($job, $stats);

        if (file_exists($filename)) {
            throw new \RuntimeException('File already exists.');
        }

        if (!file_put_contents($filename, $contents)) {
            throw new \RuntimeException('Error saving the file.');
        }
    }

    private function buildJobFileName($job, $stats)
    {
        return (int) (microtime(true) * 1000).'_'.$stats['id'].'.json';
    }

    private function renderForExport($job, $stats)
    {
        ksort($stats);

        $jobData = [
            'stats' => $stats,
            'data'  => $job->getData(),
        ];

        return json_encode((object) [
            'meta' => [
                'version' => '1.0',
                'hash'    => md5(json_encode((object) $jobData)),
            ],
            'job' => $jobData,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        $this->path = $this->argument('path');
        if (!is_dir($this->path) || !is_writable($this->path)) {
            throw new \InvalidArgumentException("Path '$this->path' doesn't exist or is not writable.");
        }

        $this->state = strtolower($this->argument('state'));
        if (!in_array($this->state, ['ready', 'buried', 'delayed'])) {
            throw new \InvalidArgumentException("Invalid state '$this->state'.");
        }

        if ($this->argument('count')) {
            if (false === ($this->count = filter_var($this->argument('count'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
                throw new \InvalidArgumentException('Count should be a positive integer.');
            }
        }
    }
}
