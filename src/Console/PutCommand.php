<?php

namespace Pvm\ArtisanBeans\Console;

class PutCommand extends BaseCommand
{
    protected $commandName = 'put';

    protected $commandArguments = '
        {tube? : Tube name}
        {body? : Plain text data for the job}
    ';

    protected $commandOptions = '
        {--f|file= : Get job data from the file}
        {--delay=0 : Delay (in seconds)}
        {--ttr=60 : TTR (in seconds)}
        {--priority=1024 : Priority}
    ';

    protected $description = 'Put a job into the tube';

    protected $delay;

    protected $ttr;

    protected $priority;

    protected $body;

    public function handle()
    {
        $this->parseArguments();

        $tube = $this->argument('tube') ?: $this->defaultTube;

        $job = $this->putJob($tube, $this->body, $this->priority, $this->delay, $this->ttr);

        $this->info("Added job with id {$job->getId()} to '$tube' with priority $this->priority, delay $this->delay, TTR $this->ttr");
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        if (is_null($this->argument('body')) && ! $this->option('file')) {
            throw new \InvalidArgumentException('You must explicitly specify the body of the job.');
        }

        if (! is_null($this->argument('body')) && $this->option('file')) {
            throw new \InvalidArgumentException('Body argument and file option are mutually exclusive.');
        }

        if (false === ($this->delay = filter_var($this->option('delay'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
            throw new \InvalidArgumentException('Delay should be a positive integer or 0.');
        }

        if (false === ($this->ttr = filter_var($this->option('ttr'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
            throw new \InvalidArgumentException('TTR should be a positive integer.');
        }

        if (false === ($this->priority = filter_var($this->option('priority'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
            throw new \InvalidArgumentException('Priority should be a positive integer or 0.');
        }

        if ($file = $this->option('file')) {
            $this->validateFile($file);
            $this->validateBodySize(filesize($file));

            if (false === $this->body = file_get_contents($file)) {
                throw new \RuntimeException("Error while trying to read '$file' file.");
            }
        }

        if (! is_null($this->argument('body'))) {
            $this->validateBodySize(strlen($this->argument('body')));
            $this->body = $this->argument('body');
        }
    }

    /**
     * @param int $size
     */
    protected function validateBodySize($size)
    {
        $maxAllowed = $this->getMaxJobSize();

        if ($size > $maxAllowed) {
            throw new \InvalidArgumentException("Body size $size is greater than max allowed $maxAllowed.");
        }
    }
}
