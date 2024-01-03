<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;

class ParseHelperService
{
    private const SUPPORT_QUALITY = [
        '2160p' => '#3840\s*[xхXХ*]\s*\d+#u',
        '1080p' => '#1920\s*[xхXХ*]\s*\d+#u',
        '720p' => '#1280\s*[xхXХ*]\s*\d+#u',
    ];

    public function getQuality(string $title, Crawler $post): string
    {
        foreach (self::SUPPORT_QUALITY as $q => $regexp) {
            if (strpos($title, $q)) {
                return $q;
            }
            if (strpos($post->text(), $q)) {
                return $q;
            }
            if (preg_match($regexp, $post->html())) {
                return $q;
            }
        }

        return '480p';
    }

    public function getImdb(Crawler $post): ?string
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
