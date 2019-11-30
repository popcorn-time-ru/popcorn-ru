<?php

namespace App\Command;

use App\Service\MovieInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tmdb\Model\Movie;
use Tmdb\Repository\MovieRepository;

class TestTmdbCommand extends Command
{
    protected static $defaultName = 'test:tmdb';

    /**
     * @var MovieInfo
     */
    private $movieInfo;

    public function __construct(MovieInfo $movieInfo)
    {
        parent::__construct();
        $this->movieInfo = $movieInfo;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->movieInfo->fetchToLocal('tt0167261');
        $this->movieInfo->fetchToLocal('tt0076759');
        $this->movieInfo->fetchToLocal('tt0241527');

        return 0;
    }
}
