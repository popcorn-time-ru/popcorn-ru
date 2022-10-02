<?php

namespace App\Spider;

use App\Entity\Torrent\BaseTorrent;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;

interface SpiderInterface
{
    public function useTor(): bool;

    public function getForumKeys(): array;

    public function getTopic(TopicDto $topic);

    public function getPage(ForumDto $forum): \Generator;

    public function getName(): string;

    public function getPriority(BaseTorrent $torrent): int;

    public function getSource(BaseTorrent $torrent): string;
}
