<?php

namespace App\Command;

use App\Service\StatService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatUpdateCommand extends Command
{
    protected static $defaultName = 'update:stat';

    /** @var StatService */
    protected $stat;

    public function __construct(StatService $stat)
    {
        parent::__construct();
        $this->stat = $stat;
    }

    protected function configure()
    {
        $this
            ->setDescription('Media statistics update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->stat->calculateMediaStat();
        return 0;
    }
}
