<?php

namespace App\Command;

use App\Repository\TorrentRepository;
use App\Service\StatService;
use Prometheus\CollectorRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

class StatUpdateCommand extends Command
{
    protected static $defaultName = 'update:stat';

    #[Required] public StatService $stat;

    protected function configure()
    {
        $this
            ->setDescription('Media statistics update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->stat->calculateMediaStat();
        $this->stat->calculateTorrentStat();
        return 0;
    }
}
