<?php

namespace App\Command;

use App\Processors\ForumProcessor;
use App\Processors\TopicProcessor;
use App\Service\TmdbExtractor;
use Enqueue\Null\NullContext;
use Interop\Amqp\Impl\AmqpMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestForumCommand extends Command
{
    protected static $defaultName = 'test:forum';

    /**
     * @var ForumProcessor
     */
    private $processor;

    public function __construct(ForumProcessor $processor)
    {
        parent::__construct();
        $this->processor = $processor;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('spider', InputArgument::REQUIRED, 'Spider')
            ->addArgument('id', InputArgument::REQUIRED, 'Id')
            ->addArgument('page', InputArgument::OPTIONAL, 'page', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->process(
            new AmqpMessage(json_encode([
                'spider' => $input->getArgument('spider'),
                'forumId' => $input->getArgument('id'),
                'page' => $input->getArgument('page'),
            ])),
            new NullContext()
        );

        return 0;
    }
}
