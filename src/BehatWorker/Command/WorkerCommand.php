<?php

namespace BehatWorker\Command;

use BehatWorker\BehatWorker;
use BehatWorker\FeatureScanner;
use Behat\Testwork\Counter\Timer;
use QXS\WorkerPool\WorkerPool;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Command
{
    protected function configure()
    {
        $this->setName('worker:start')
            ->setDescription('Run behat worker.')
            ->setHelp("This command allows you execute several behats instances.")
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption('workers', 'w', InputOption::VALUE_REQUIRED, 'Number of behat processes', 1),
                    new InputOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to features folder'),
                    new InputOption('output', 'o', InputOption::VALUE_REQUIRED, 'Path for reports'),
                    new InputOption('tags', 't', InputOption::VALUE_OPTIONAL, 'Tags to filter scenarios'),
                ))
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bw = new WorkerPool();
        $bw->setWorkerPoolSize($input->getOption('workers'))->create(new BehatWorker());

        $scanner = new FeatureScanner($input->getOption('path'));
        $featuresPaths = $scanner->getFeaturesList();

        $timer = new Timer();
        $timer->start();
        exec('mkdir -p ' . $input->getOption('output'));
        foreach ($featuresPaths as $path) {
            $pathParts = $parts = explode('/', str_replace('.feature', '', $path));
            $name = end($pathParts);
            $bw->run(
                [
                    'feature' => $path,
                    'arguments' => $input->getOption('tags'),
                    'config' => str_replace(
                        $input->getOption('output'),
                        $input->getOption('output') . DIRECTORY_SEPARATOR . $name,
                        getenv('BEHAT_PARAMS')
                    ),
                ]
            );
        }

        $bw->waitForAllWorkers();

        $output->writeln('');
        $output->writeln('<info>Test result</info>');
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Feature', 'Time']);

        foreach ($bw as $dataRow) {
            if (!is_null($dataRow['data'])) {
                $table->addRow([$dataRow['data']['feature'], $dataRow['data']['time'] ] );
            }
        }
        $table->render();
        $output->writeln(["<info>Testing " . count($featuresPaths) . ' features took: ' . $timer->getMinutes() . 'm' . $timer->getSeconds() .'s</info>']);

    }
}
