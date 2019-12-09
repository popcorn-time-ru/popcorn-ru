<?php

namespace App\Spider\Dto;

class ForumDto
{
    /** @var string */
    public $id;

    /** @var integer */
    public $page;

    /** @var integer */
    public $delay;

    /**
     * ForumDto constructor.
     *
     * @param string $id
     * @param int    $page
     * @param int    $delay
     */
    public function __construct(string $id, int $page = 1, int $delay = 0)
    {
        $this->id = $id;
        $this->page = $page;
        $this->delay = $delay;
    }
}
