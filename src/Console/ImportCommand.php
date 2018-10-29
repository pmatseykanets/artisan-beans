<?php

namespace Pvm\ArtisanBeans\Console;

class ImportCommand extends BaseCommand
{
    protected $commandName = 'import';

    protected $commandArguments = '
        {path : Path to a job file. Patterns are supported (i.e. *.json)}
        {tube? : Tube name}
    ';

    protected $commandOptions = '
        {--delay=0 : Delay (in seconds)}
        {--ttr= : New TTR (in seconds)}
        {--priority= : New priority}
    ';

    protected $description = 'Import jobs';

    protected $path;

    protected $delay;

    protected $ttr;

    protected $priority;

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->parseArguments();

        $imported = 0;

        $files = $this->getJobFiles($this->path);

        if (($totalFiles = count($files)) > 1) {
            if (! $this->confirmToProceed("You are about to import $totalFiles jobs.")) {
                return;
            }
        }

        foreach ($files as $filename) {
            try {
                $this->importJob($filename, $this->argument('tube'), $this->option('delay'), $this->option('ttr'), $this->option('priority'));
                $imported++;
            } catch (\RuntimeException $e) {
                $this->reportImported($imported);

                throw $e;
            }
        }

        $this->reportImported($imported);
    }

    private function reportImported($imported)
    {
        $this->comment("Imported $imported jobs.");
    }

    /**
     * @param $path
     *
     * @return array
     */
    private function getJobFiles($path)
    {
        $files = [];

        foreach (glob($path) as $filename) {
            if (is_dir($filename)) {
                continue;
            }

            $files[] = $filename;
        }

        return $files;
    }

    /**
     * @param $filename
     * @param $tube
     * @param int  $delay
     * @param null $ttr
     * @param null $priority
     */
    private function importJob($filename, $tube = null, $delay = 0, $ttr = null, $priority = null)
    {
        if (0 == filesize($filename)) {
            throw new \RuntimeException("File '$filename' is empty.");
        }

        $data = json_decode(file_get_contents($filename));

        if ((is_null($data) && JSON_ERROR_NONE !== json_last_error()) ||
            ! isset($data->meta->version) ||
            ! isset($data->meta->hash) ||
            ! isset($data->job->data) ||
            ! isset($data->job->stats) ||
            '1.0' !== $data->meta->version) {
            throw new \RuntimeException("File '$filename' is not a valid job file or has an unsupported format.");
        }

        $hash = md5(json_encode($data->job));
        if ($hash !== $data->meta->hash) {
            throw new \RuntimeException("The contents of the file '$filename' has changed.");
        }

        $tube = $tube ?: $data->job->stats->tube;
        $ttr = $ttr ?: $data->job->stats->ttr;
        $priority = $priority ?: $data->job->stats->pri;

        $this->putJob($tube, $data->job->data, $priority, $delay, $ttr);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        $this->path = $this->argument('path');

        if ($this->option('delay')) {
            if (false === ($this->delay = filter_var($this->option('delay'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
                throw new \InvalidArgumentException('Delay should be a positive integer or 0.');
            }
        }

        if ($this->option('ttr')) {
            if (false === ($this->ttr = filter_var($this->option('ttr'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
                throw new \InvalidArgumentException('TTR should be a positive integer.');
            }
        }

        if ($this->option('priority')) {
            if (false === ($this->priority = filter_var($this->option('priority'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
                throw new \InvalidArgumentException('Priority should be a positive integer or 0.');
            }
        }
    }
}
