<?php

namespace App\Command;

use App\Repository\TorrentRepository;
use App\Service\StatService;
use Prometheus\CollectorRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Service\Attribute\Required;

#[AsCommand(
    name: 'update:stat',
    description: 'Media statistics update',
)]
class StatUpdateCommand extends Command
{
    #[Required] public StatService $stat;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->stat->calculateMediaStat();
        $this->stat->calculateTorrentStat();
        return 0;
    }
}
