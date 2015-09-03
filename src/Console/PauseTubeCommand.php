<?php

namespace Pvm\ArtisanBeans\Console;

use Pheanstalk\Exception\ServerException;

class PauseTubeCommand extends BaseCommand
{
    protected $commandName = 'pause';

    protected $commandArguments = '
        {tube : Tube name}
        {delay : Seconds before jobs can be reserved from the tube}
    ';

    protected $description = 'Pause the tube';

    protected $delay;

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->parseArguments();

        $tube = $this->argument('tube');

        try {
            $this->getPheanstalk()->pauseTube($tube, $this->delay);
        } catch (ServerException $e) {
            if ($this->isNotFoundException($e)) {
                return $this->comment("Tube '$tube' doesn't exist.");
            }

            throw $e;
        }

        return $this->comment($this->getSuccessMessage($tube));
    }

    /**
     * {@inheritdoc}
     */
    protected function parseCommandArguments()
    {
        if (false === ($this->delay = filter_var($this->argument('delay'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
            throw new \InvalidArgumentException('Delay should be a positive integer.');
        }
    }

    /**
     * @param $tube
     *
     * @return string
     */
    protected function getSuccessMessage($tube)
    {
        return "Tube '$tube' has been paused for $this->delay sec.";
    }
}
