<?php

namespace App\Command;

use App\Processors\TopicProcessor;
use App\Service\TmdbExtractor;
use Enqueue\Null\NullContext;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestTmdbCommand extends Command
{
    protected static $defaultName = 'test:tmdb';

    /**
     * @var TmdbExtractor
     */
    private $movieInfo;

    /**
     * @var TopicProcessor
     */
    private $processor;

    public function __construct(TmdbExtractor $movieInfo, TopicProcessor $processor)
    {
        parent::__construct();
        $this->movieInfo = $movieInfo;
        $this->processor = $processor;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('spider', InputArgument::REQUIRED, 'Spider')
            ->addArgument('id', InputArgument::REQUIRED, 'Id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->process(
            new AmqpMessage(json_encode([
                'spider' => $input->getArgument('spider'),
                'topicId' => $input->getArgument('id'),
                'info' => ['seed' => '10', 'leech' => '1'],
            ])),
            new NullContext()
        );

        return 0;
    }
}
