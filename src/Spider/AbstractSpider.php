<?php

namespace App\Spider;

abstract class AbstractSpider implements SpiderInterface
{
    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
