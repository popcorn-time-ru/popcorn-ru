<?php

namespace App\Command;

use App\Processors\TopicProcessor;
use App\Service\MediaService;
use Enqueue\Null\NullContext;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestTopicCommand extends Command
{
    protected static $defaultName = 'test:topic';

    /**
     * @var TopicProcessor
     */
    private $processor;

    public function __construct(TopicProcessor $processor)
    {
        parent::__construct();
        $this->processor = $processor;
    }

    protected function configure()
    {
        $this
            ->setDescription('Extract torrent from tracker')
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
                'seed' => 0,
                'leech' => 0
            ])),
            new NullContext()
        );

        return 0;
    }
}
