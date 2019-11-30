<?php

namespace App\Spider;

interface SpiderInterface
{
    public function getForumKeys(): array;

    public function getTopic($topicId);

    public function getPage($forumId, $page): \Generator;

    public function getName(): string;
}
