<?php

namespace Pvm\ArtisanBeans\Console;

use Illuminate\Console\Command;
use Pheanstalk\Exception\ServerException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Response;

abstract class BaseCommand extends Command
{
    protected $signature;

    protected $namespace = 'beans';

    protected $commonOptions = '
        {--c|connection= : Beanstalkd connection to use}
        {--H|host= : Beanstalkd Host}
        {--P|port= : Beanstalkd Port}
    ';

    protected $commandArguments = '';

    protected $commandOptions = '';

    protected $host = 'localhost';

    protected $port;

    protected $defaultTube;

    protected $pheanstalk;

    public function __construct()
    {
        $this->buildCommandSignature();

        parent::__construct();

        $this->port = Pheanstalk::DEFAULT_PORT;
        $this->defaultTube = Pheanstalk::DEFAULT_TUBE;
    }

    protected function peekJob($tube, $state)
    {
        $peekMethod = 'peek'.ucfirst($this->state);

        try {
            return $this->getPheanstalk()->$peekMethod($tube);
        } catch (ServerException $e) {
            if ($this->isNotFoundException($e)) {
                return;
            }

            throw $e;
        }
    }

    protected function reserveJob($tube)
    {
        try {
            return $this->getPheanstalk()->reserveFromTube($tube, 0);
        } catch (ServerException $e) {
            if ($this->isNotFoundException($e)) {
                return;
            }

            throw $e;
        }
    }

    protected function getJobStats($job)
    {
        try {
            return $this->getPheanstalk()->statsJob($job);
        } catch (ServerException $e) {
            if ($this->isNotFoundException($e)) {
                return;
            }

            throw $e;
        }
    }

    /**
     * @param $job
     */
    protected function deleteJob($job)
    {
        $this->getPheanstalk()->delete($job);
    }

    /**
     * @param $job
     * @param int $priority New priority
     */
    protected function buryJob($job, $priority = null)
    {
        if (is_null($priority)) {
            $priority = Pheanstalk::DEFAULT_PRIORITY;
        }

        $this->getPheanstalk()->bury($job, $priority);
    }

    /**
     * @param $tube
     * @param int $count
     *
     * @return int
     */
    protected function kickJob($tube, $count = 1)
    {
        return $this->getPheanstalk()
            ->useTube($tube)
            ->kick($count);
    }

    /**
     * @param string $tube
     * @param string $body
     * @param int    $priority
     * @param int    $delay
     * @param int    $ttr
     *
     * @return int
     */
    protected function putJob($tube, $body, $priority, $delay, $ttr)
    {
        $id = $this->getPheanstalk()
            ->putInTube($tube, $body, $priority, $delay, $ttr);

        return $id;
    }

    /**
     * @return Pheanstalk
     */
    public function getPheanstalk()
    {
        if (!$this->pheanstalk) {
            $this->pheanstalk = new Pheanstalk($this->host, $this->port);
        }

        return $this->pheanstalk;
    }

    /**
     *
     */
    protected function parseArguments()
    {
        $this->parseConnection($this->option('connection'));

        if ($this->option('host')) {
            $this->host = $this->option('host');
        }

        if ($this->option('port')) {
            $this->port = (int) $this->option('port');
        }

        $this->parseCommandArguments();
    }

    protected function parseCommandArguments()
    {
    }

    /**
     * @param $connectionName
     */
    protected function parseConnection($connectionName)
    {
        $connection = null;

        // If user provided the connection name read it directly
        if ($connectionName) {
            if (!$connection = config("queue.connections.$connectionName")) {
                throw new \InvalidArgumentException("Connection '$connectionName' doesn't exist.");
            }
        }

        // Try default connection
        if (!$connection) {
            $defaultConnection = config('queue.default');

            if ('beanstalkd' == config("queue.connections.$defaultConnection.driver")) {
                $connection = config("queue.connections.$defaultConnection");
            }
        }

        // Try first connection that has beanstalkd driver
        if (!$connection) {
            foreach (config('queue.connections') as $connection) {
                if ('beanstalkd' == $connection['driver']) {
                    break;
                }
            }
        }

        if (!empty($connection['host'])) {
            $parsedConfigHost = explode(':', $connection['host']);

            $this->host = $parsedConfigHost[0];

            if (isset($parsedConfigHost[1])) {
                $this->port = $parsedConfigHost[1];
            }
        }

        if (!empty($connection['queue'])) {
            $this->defaultTube = $connection['queue'];
        }
    }

    /**
     *
     */
    protected function buildCommandSignature()
    {
        $this->signature = $this->namespace.':'.$this->commandName.' '.
            $this->commandArguments.
            $this->commandOptions.
            $this->commonOptions;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function transformForTable($data)
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = [$key, $value];
        }

        return $result;
    }

    /**
     * Validates the file exists, is readable and optionaly is not empty
     * and returns an absolute path to the file.
     *
     * @param $filePath
     * @param string $message
     *
     * @return string
     */
    protected function validateFile($filePath, $message = 'File', $allowEmpty = true)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException("$message '{$filePath}' doesn't exist or is not readable.");
        }

        if (!$allowEmpty && 0 === filesize($filePath)) {
            throw new \RuntimeException("$message '{$filePath}' is empty.");
        }

        return realpath($filePath);
    }

    /**
     * @param \Exception $e
     *
     * @return bool
     */
    protected function isNotFoundException(\Exception $e)
    {
        return false !== strpos($e->getMessage(), Response::RESPONSE_NOT_FOUND);
    }

    /**
     * Renders the job to the command output.
     *
     * @param $job
     */
    protected function renderJob($job)
    {
        $stats = $this->getJobStats($job);

        $format = '<info>id</info>: %u, <info>length</info>: %u, <info>priority</info>: %u, <info>delay</info>: %u, <info>age</info>: %u, <info>ttr</info>: %u';
        $line = sprintf($format, $job->getId(), strlen($job->getData()), $stats['pri'], $stats['delay'], $stats['age'], $stats['ttr']);
        $this->output->writeln($line);

        $format = '<comment>reserves</comment>: %u, <comment>releases</comment>: %u, <comment>buries</comment>: %u, <comment>kicks</comment>: %u, <comment>timeouts</comment>: %u';
        $line = sprintf($format, $stats['reserves'], $stats['releases'], $stats['buries'], $stats['kicks'], $stats['timeouts']);
        $this->output->writeln($line);

        $this->output->writeln('<comment>body:</comment>');

        $data = $job->getData();
        $this->output->writeln("\"$data\"");
    }

    /**
     * @param $tube
     * @param $state
     */
    protected function renderJobNotFoundMessage($tube, $state = 'ready')
    {
        $message = "Tube '$tube'".('default' == $tube ? '' : " doesn't exist or")." has no jobs in '$state' state.";

        $this->comment($message);
    }

    /**
     * @return int
     */
    protected function getMaxJobSize()
    {
        $stats = $this->getPheanstalk()->stats();

        return isset($stats['max-job-size']) ? (int) $stats['max-job-size'] : 65535;
    }
}
