<?php

namespace Pvm\ArtisanBeans\Console;

class PurgeCommand extends DeleteCommand
{
    protected $commandName = 'purge';

    protected $commandArguments = '
        {state=ready : State [ready|delayed|buried]}
        {tube? : Tube name}
        {count=0 : Number of jobs to delete}
    ';

    protected $description = 'Purge jobs from the tube';

    protected $count = 0;

    public function handle()
    {
        $this->parseArguments();

        $tube = $this->argument('tube') ?: $this->defaultTube;

        $this->comment('You are about to delete '.($this->count ?: 'all')." '$this->state' jobs in '$tube' tube.");
        if (!$this->confirm('Are you sure you want to proceed?')) {
            return;
        }

        $deleted = 0;
        while ($job = $this->peekJob($tube, $this->state)) {
            if ($this->count > 0 && $deleted >= $this->count) {
                break;
            }

            $this->deleteJob($job);

            ++$deleted;
        }

        if (0 == $deleted) {
            $this->renderJobNotFoundMessage($tube, $this->state);
        } else {
            $this->comment("Deleted $deleted jobs from '$tube' tube in '$this->state' state.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        parent::parseCommandArguments();

        if ($this->argument('count')) {
            if (false === ($this->count = filter_var($this->argument('count'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
                throw new \InvalidArgumentException('Count should be a positive integer.');
            }
        }
    }
}
