<?php

namespace App\Spider;

use App\Service\TorrentService;
use DateTime;
use Psr\Log\LoggerInterface;
use ReflectionClass;
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
        return (new ReflectionClass($this))->getShortName();
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

    protected function ruStrToTime(string $format, string $time): DateTime
    {
        $ru = ['Янв', 'Фев', 'Мар', 'Апр', 'Июн', 'Май', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
        $en = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct',' nov', 'dec'];
        $time = str_replace($ru, $en, $time);
        return DateTime::createFromFormat($format, $time);
    }

    protected function getImdb(Crawler $post): ?string
    {

        $links = $post->filter('a[href*="imdb.com"]')->each(function (Crawler $c) {
            preg_match('#tt\d+#', $c->attr('href'), $m);
            return $m[0] ?? false;
        });

        $ids = array_unique(array_filter($links));

        // пропускаем сборники
        return count($ids) == 1 ? current($ids) : null;
    }
}
