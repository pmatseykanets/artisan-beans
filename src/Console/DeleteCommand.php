<?php

namespace Pvm\ArtisanBeans\Console;

class DeleteCommand extends PeekCommand
{
    protected $commandName = 'delete';

    protected $description = 'Delete a job';

    protected $state = 'ready';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->parseArguments();

        $tube = $this->argument('tube') ?: $this->defaultTube;

        if (!$job = $this->peekJob($tube, $this->state)) {
            return $this->renderJobNotFoundMessage($tube, $this->state);
        }

        $this->renderJob($job);

        if (!$this->confirm('Are you sure you want to delete this job?')) {
            return;
        }

        $this->deleteJob($job);

        $this->comment('The job has been deleted.');
    }
}
