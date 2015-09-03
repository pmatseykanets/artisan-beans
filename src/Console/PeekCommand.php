<?php

namespace Pvm\ArtisanBeans\Console;

class PeekCommand extends BaseCommand
{
    protected $commandName = 'peek';

    protected $commandArguments = '
        {state=ready : State [ready|delayed|buried]}
        {tube? : Tube name}
    ';

    protected $commandOptions = '';

    protected $description = 'Peek a job';

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
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        $this->state = strtolower($this->argument('state'));
        if (!in_array($this->state, ['ready', 'buried', 'delayed'])) {
            throw new \InvalidArgumentException("Invalid state '$this->state'.");
        }
    }
}
