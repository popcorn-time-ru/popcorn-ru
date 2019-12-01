<?php

namespace App\Spider;

interface SpiderInterface
{
    public function getForumKeys(): array;

    public function getTopic($topicId, array $info);

    public function getPage($forumId, $page): \Generator;

    public function getName(): string;
}
