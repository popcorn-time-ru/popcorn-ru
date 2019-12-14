<?php

namespace App\Command;

use App\Processors\ForumProcessor;
use App\Service\SpiderSelector;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SpiderRunCommand extends Command
{
    protected static $defaultName = 'spider:run';

    /** @var SpiderSelector */
    protected $selector;

    /** @var ProducerInterface */
    private $producer;

    public function __construct(SpiderSelector $selector, ProducerInterface $producer)
    {
        parent::__construct();
        $this->selector = $selector;
        $this->producer = $producer;
    }

    protected function configure()
    {
        // ежедневно последние 48 часов все
        // все при старте, и при добавлении нового трекера или изменения логики
        $this
            ->setDescription('Run spider')
            ->addArgument('name', InputArgument::IS_ARRAY, 'Spider name')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Run all spiders')
            ->addOption('last', null, InputOption::VALUE_REQUIRED, 'Only last N hours')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $spiders = $input->getArgument('name');

        if ($input->getOption('all')) {
            $spiders = array_keys($this->selector->gerAll());
        }
        $last = $input->getOption('last');

        foreach ($spiders as $spider) {
            $io->title('Start Processing '.$spider);
            $keys = $this->selector->get($spider)->getForumKeys();
            foreach ($keys as $key) {
                $message = new Message(json_encode(['spider' => $spider, 'forumId' => $key, 'page' => 1, 'last' => $last]));
                $this->producer->sendEvent(
                    ForumProcessor::TOPIC,
                    $message
                );
                $io->text('Send forum '.$key);
            }
        }

        return 0;
    }
}
