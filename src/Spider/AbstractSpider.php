<?php

namespace App\Spider;

use App\Service\TorrentService;
use Psr\Log\LoggerInterface;

abstract class AbstractSpider implements SpiderInterface
{
    /** @var TorrentService */
    protected $torrentService;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(TorrentService $torrentService, LoggerInterface $logger)
    {
        $this->torrentService = $torrentService;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
