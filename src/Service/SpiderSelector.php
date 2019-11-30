<?php

namespace App\Service;

use App\Spider\SpiderInterface;

class SpiderSelector
{
    /** @var SpiderInterface[] */
    protected $spiders;

    public function __construct(iterable $spiders)
    {
        foreach ($spiders as $spider) {
            /** @var SpiderInterface $spider */
            $this->spiders[$spider->getName()] = $spider;
        }
    }

    public function get(string $name): ?SpiderInterface
    {
        return $this->spiders[$name] ?? null;
    }

    /**
     * @return SpiderInterface[]
     */
    public function gerAll(): array
    {
        return $this->spiders;
    }
}
