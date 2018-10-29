<?php

namespace Pvm\ArtisanBeans\Console;

use Pheanstalk\Response;
use Pheanstalk\Pheanstalk;
use Illuminate\Console\Command;
use Pheanstalk\Exception\ServerException;

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

    /**
     * Peek a job in a spesific state.
     *
     * @param $tube
     * @param $state
     *
     * @throws ServerException
     * @throws \Exception
     */
    protected function peekJob($tube, $state)
    {
        $peekMethod = 'peek'.ucfirst($state);

        try {
            return $this->getPheanstalk()->$peekMethod($tube);
        } catch (ServerException $e) {
            if ($this->isNotFoundException($e)) {
                return;
            }

            throw $e;
        }
    }

    /**
     * Reserve a job from the tube.
     *
     * @param $tube
     *
     * @throws ServerException
     * @throws \Exception
     *
     * @return bool|object|\Pheanstalk\Job|void
     */
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

    /**
     * Returns the job's statistics.
     *
     * @param $job
     *
     * @throws ServerException
     * @throws \Exception
     *
     * @return array
     */
    protected function getJobStats($job)
    {
        try {
            return (array) $this->getPheanstalk()->statsJob($job);
        } catch (ServerException $e) {
            if ($this->isNotFoundException($e)) {
                return;
            }

            throw $e;
        }
    }

    /**
     * Delete the job.
     *
     * @param $job
     */
    protected function deleteJob($job)
    {
        $this->getPheanstalk()->delete($job);
    }

    /**
     * Bury the job.
     *
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
     * Kick the job.
     *
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
     * Puts a job in the queue.
     *
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
     * Returns a Pheanstalk instance.
     *
     * @return Pheanstalk
     */
    public function getPheanstalk()
    {
        if (! $this->pheanstalk) {
            $this->pheanstalk = new Pheanstalk($this->host, $this->port);
        }

        return $this->pheanstalk;
    }

    /**
     * Generic logic for reading ang validating command's arguments and options.
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

    /**
     * Command specific logic for reading ang validating arguments and options.
     */
    protected function parseCommandArguments()
    {
    }

    /**
     * Tries to figure out and set host, port and default tube
     * from the Laravel's queue.php config.
     *
     * @param $connectionName
     */
    protected function parseConnection($connectionName)
    {
        $connection = null;

        // If user provided the connection name read it directly
        if ($connectionName) {
            if (! $connection = config("queue.connections.$connectionName")) {
                throw new \InvalidArgumentException("Connection '$connectionName' doesn't exist.");
            }
        }

        // Try default connection
        if (! $connection) {
            $defaultConnection = config('queue.default');

            if ('beanstalkd' == config("queue.connections.$defaultConnection.driver")) {
                $connection = config("queue.connections.$defaultConnection");
            }
        }

        // Try first connection that has beanstalkd driver
        if (! $connection) {
            foreach (config('queue.connections') as $connection) {
                if ('beanstalkd' == $connection['driver']) {
                    break;
                }
            }
        }

        if (! empty($connection['host'])) {
            $parsedConfigHost = explode(':', $connection['host']);

            $this->host = $parsedConfigHost[0];

            if (isset($parsedConfigHost[1])) {
                $this->port = $parsedConfigHost[1];
            }
        }

        if (! empty($connection['queue'])) {
            $this->defaultTube = $connection['queue'];
        }
    }

    /**
     * Build a command's signature.
     *
     * @return string
     */
    protected function buildCommandSignature()
    {
        $this->signature = $this->namespace.':'.$this->commandName.' '.
            $this->commandArguments.
            $this->commandOptions.
            $this->commonOptions;
    }

    /**
     * Transforms an assoc array into a multidementional one
     * which is expected by helper table() method.
     *
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
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new \RuntimeException("$message '{$filePath}' doesn't exist or is not readable.");
        }

        if (! $allowEmpty && 0 === filesize($filePath)) {
            throw new \RuntimeException("$message '{$filePath}' is empty.");
        }

        return realpath($filePath);
    }

    /**
     * If a job or other object doesn't exist beanstalkd returns NOT_FOUND response
     * We use is to determine whether we need to display a normal message
     * or re-throw an exception.
     *
     * @param \Exception $e
     *
     * @return bool
     */
    protected function isNotFoundException(\Exception $e)
    {
        return false !== strpos($e->getMessage(), Response::RESPONSE_NOT_FOUND);
    }

    /**
     * Displays job information.
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
     * Displays message.
     *
     * @param $tube
     * @param $state
     */
    protected function renderJobNotFoundMessage($tube, $state = 'ready')
    {
        $message = "Tube '$tube'".('default' == $tube ? '' : " doesn't exist or")." has no jobs in '$state' state.";

        $this->comment($message);
    }

    /**
     * Returns the maximum allowed size for a job body.
     *
     * @return int
     */
    protected function getMaxJobSize()
    {
        $stats = $this->getServerStats('max-job-size');

        return isset($stats['max-job-size']) ? (int) $stats['max-job-size'] : 65535;
    }

    /**
     * @param null   $message
     * @param string $question
     *
     * @return bool
     */
    protected function confirmToProceed($message = null, $question = 'Are you sure you want to proceed?')
    {
        if ($message) {
            $this->comment($message);
        }

        return $this->confirm($question);
    }

    /**
     * Returns statistics for the tube.
     *
     * @param $tube
     *
     * @throws ServerException
     * @throws \Exception
     *
     * @return object|\Pheanstalk\Response
     */
    protected function getTubeStats($tube)
    {
        try {
            $stats = $this->getPheanstalk()->statsTube($tube);
        } catch (ServerException $e) {
            if ($this->isNotFoundException($e)) {
                throw new \RuntimeException("Tube '$tube' doesn't exist.");
            }

            throw $e;
        }

        return $stats;
    }

    /**
     * Lists all currently available tubes.
     *
     * @return array
     */
    protected function getTubes()
    {
        return (array) $this->getPheanstalk()->listTubes();
    }

    /**
     * Lists server statistics optionaly filtering keys by a pattern.
     *
     * @param string $pattern
     *
     * @return array
     */
    protected function getServerStats($pattern = '')
    {
        $stats = (array) $this->getPheanstalk()->stats();

        if (! empty($pattern)) {
            $stats = array_filter($stats, function ($key) use ($pattern) {
                return 1 === preg_match("/$pattern/i", $key);
            }, ARRAY_FILTER_USE_KEY);
        }

        ksort($stats);

        return $stats;
    }
}
