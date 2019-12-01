<?php

namespace App\Spider;

use App\Service\TorrentSrvice;
use Psr\Log\LoggerInterface;

abstract class AbstractSpider implements SpiderInterface
{
    /** @var TorrentSrvice */
    protected $torrentService;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(TorrentSrvice $torrentService, LoggerInterface $logger)
    {
        $this->torrentService = $torrentService;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
