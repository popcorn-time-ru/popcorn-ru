<?php

namespace App\Spider;

class Rutor extends AbstractSpider
{
    public const BASE_URL = 'http://tor-mega.top';

    public function getForumKeys(): array
    {
        return [
            1, // Зарубежные Фильмы
        ];
    }

    public function getPage($forumId, $page): \Generator
    {
        yield 1;
        return 2;
    }

    public function getTopic($topicId)
    {

    }
}
