<?php

namespace App\Spider;

use App\Service\TorrentService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

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

    protected function getQuality(Crawler $post): string
    {
        if (strpos($post->text(), '1080p')) {
            return '1080p';
        }
        if (strpos($post->text(), '720p')) {
            return '720p';
        }
        if (preg_match('#1920\s*[xхXХ*]\s*\d+#u', $post->html())) {
            return '1080p';
        }
        if (preg_match('#1280\s*[xхXХ*]\s*\d+#u', $post->html())) {
            return '720p';
        }

        return '480p';
    }

}
