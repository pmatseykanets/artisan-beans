<?php

namespace Pvm\ArtisanBeans\Console;

class BuryCommand extends BaseCommand
{
    protected $commandName = 'bury';

    protected $commandArguments = '
        {tube? : Tube name}
        {count=1 : Number of jobs to bury}
    ';

    protected $commandOptions = '
        {--priority= : New priority}
    ';

    protected $description = 'Bury a job';

    protected $count = 1;

    protected $priority;

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->parseArguments();

        $tube = $this->argument('tube') ?: $this->defaultTube;

        if ($this->count > 1) {
            if (!$this->confirmToProceed("You are about to bury $this->count jobs in '$tube' tube.")) {
                return;
            }
        }

        $buried = 0;
        while ($job = $this->reserveJob($tube)) {
            if ($this->count > 0 && $buried >= $this->count) {
                break;
            }

            $this->buryJob($job, $this->priority);

            $buried++;
        }

        if (0 == $buried) {
            $this->renderJobNotFoundMessage($tube, 'ready');
        } else {
            $this->comment("Buried $buried jobs in '$tube' tube.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        if ($this->argument('count')) {
            if (false === ($this->count = filter_var($this->argument('count'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
                throw new \InvalidArgumentException('Count should be a positive integer.');
            }
        }

        if (!is_null($this->option('priority'))) {
            if (false === ($this->priority = filter_var($this->option('priority'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
                throw new \InvalidArgumentException('Priority should be a positive integer or 0.');
            }
        }
    }
}
