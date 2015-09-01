<?php

namespace Pvm\ArtisanBeans\Console;

class KickCommand extends BaseCommand
{
    protected $commandName = 'kick';

    protected $commandArguments = '
        {tube? : Tube name}
        {count=1 : Number of jobs to kick}
    ';

    protected $description = 'Kick a job';

    protected $count = 1;

    public function handle()
    {
        $this->parseArguments();

        $tube = $this->argument('tube') ?: $this->defaultTube;

        if ($this->count > 1) {
            $this->comment("You are about to kick $this->count jobs in '$tube' tube.");
            if (!$this->confirm('Are you sure you want to proceed?')) {
                return;
            }
        }

        $kicked = $this->kickJob($tube, $this->count);

        if (0 == $kicked) {
            $this->renderJobNotFoundMessage($tube);
        } else {
            $this->comment("Kicked $kicked jobs in '$tube' tube.");
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
