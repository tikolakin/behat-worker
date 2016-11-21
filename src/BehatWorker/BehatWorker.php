<?php
namespace BehatWorker;

use QXS\WorkerPool\WorkerInterface;
use QXS\WorkerPool\Semaphore;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Behat\Testwork\Counter\Timer;

class BehatWorker implements WorkerInterface
{

    protected $semaphore;

    protected $behatOutput;

    protected $feature;

    protected $arguments;

    protected $config;

    protected $timeOut;

    public function onProcessCreate(Semaphore $semaphore)
    {
        $this->semaphore = $semaphore;
    }

    public function onProcessDestroy()
    {
    }

    public function run($input)
    {
        $timer = new Timer();
        $timer->start();

        $this->feature = $input['feature'];
        $this->arguments = $input['arguments'];
        $this->config = $input['config'];

        $cmd = sprintf('exec ./vendor/behat/behat/bin/behat --tags="%s" %s', $this->arguments, $this->feature);
        $behatProcess = new Process($cmd, PROJECT_PATH, ['BEHAT_PARAMS' => $this->config], null, 1200);
        $behatProcess->enableOutput();
        $this->behatOutput = '';
        try {
            $behatProcess->mustRun(function ($input, $data) use ($behatProcess){
                $this->behatOutput .= $data;
            });
            echo 'SUCCESS: ', $this->feature, PHP_EOL;
        } catch (ProcessFailedException $e) {
            if (strpos($this->behatOutput, '[Behat\Testwork\Tester\Exception\WrongPathsException]') !== false) {
                echo 'No tests were ran at ', $this->feature, PHP_EOL;
                return null;
            } else {
                echo 'Behat process error for ', $this->feature, ': ' , PHP_EOL , $e->getMessage(), PHP_EOL;
            }
        } catch (ProcessTimedOutException $e) {
            echo 'Behat process timed out for ', $this->feature, ', message: ', $e->getMessage(), PHP_EOL;
        }
        return ['feature' => $this->feature, 'time' => $timer->getMinutes() . 'm' . $timer->getSeconds() .'s'];
    }
}
