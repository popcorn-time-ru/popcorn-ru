<?php

namespace App\Spider;

use App\Entity\Torrent\BaseTorrent;

interface DownloadInterface
{
    public function downloadTorrent(BaseTorrent $torrent, string $downloadId);
}
