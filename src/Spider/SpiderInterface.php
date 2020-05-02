<?php

namespace App\Spider;

use App\Entity\Torrent\BaseTorrent;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;

interface SpiderInterface
{
    public function getForumKeys(): array;

    public function getTopic(TopicDto $topic);

    public function getPage(ForumDto $forum): \Generator;

    public function getName(): string;

    public function getSource(BaseTorrent $torrent): string;
}
