<?php

namespace Pvm\ArtisanBeans\Console;

class MoveCommand extends BaseCommand
{
    protected $commandName = 'move';

    protected $commandArguments = '
        {from : Source tube name}
        {to : Destination tube name}
        {state=ready : State [ready|delayed|buried] in the source tube}
        {count=1 : Number of jobs to move}
    ';

    protected $commandOptions = '
        {--delay=0 : Delay (in seconds)}
        {--ttr= : New TTR (in seconds)}
        {--priority= : New priority}
    ';

    protected $description = 'Move jobs between tubes';

    protected $state;

    protected $count;

    protected $delay;

    protected $priority;

    protected $ttr;

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->parseArguments();

        if ($this->count > 1) {
            if (! $this->confirmToProceed("You are about to move $this->count jobs from '".$this->argument('from')."' to '".$this->argument('to')."'.")) {
                return;
            }
        }

        $moved = 0;
        while ($job = $this->getNextJob($this->argument('from'), $this->state)) {
            if ($this->count > 0 && $moved >= $this->count) {
                break;
            }
            // Read the job's stats in order to preserve priority and ttr
            $stats = $this->getJobStats($job);

            $this->putJob($this->argument('to'), $job->getData(), $this->priority ?: $stats['pri'], $this->delay, $this->ttr ?: $stats['ttr']);

            $this->deleteJob($job);

            $moved++;
        }

        if (0 == $moved) {
            $this->renderJobNotFoundMessage($this->argument('from'), $this->state);
        } else {
            $this->comment("Moved $moved jobs from '".$this->argument('from')."' to '".$this->argument('to')."'.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        $this->state = strtolower($this->argument('state'));
        if (! in_array($this->state, ['ready', 'buried', 'delayed'])) {
            throw new \InvalidArgumentException("Invalid state '$this->state'.");
        }

        if (false === ($this->count = filter_var($this->argument('count'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
            throw new \InvalidArgumentException('Count should be a positive integer.');
        }

        if (false === ($this->delay = filter_var($this->option('delay'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
            throw new \InvalidArgumentException('Delay should be a positive integer or 0.');
        }

        if (! is_null($this->option('ttr'))) {
            if (false === ($this->ttr = filter_var($this->option('ttr'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
                throw new \InvalidArgumentException('TTR should be a positive integer.');
            }
        }

        if (! is_null($this->option('priority'))) {
            if (false === ($this->priority = filter_var($this->option('priority'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]))) {
                throw new \InvalidArgumentException('Priority should be a positive integer or 0.');
            }
        }
    }

    /**
     * Fetches the next job from the tube.
     * For ready jobs do reserve, for delayed and buried - peek.
     *
     * @param $tube
     * @param $state
     *
     * @return bool|object|\Pheanstalk\Job|void
     */
    private function getNextJob($tube, $state)
    {
        if ('ready' == $this->state) {
            return $this->reserveJob($tube);
        }

        return $this->peekJob($tube, $state);
    }
}
