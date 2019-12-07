<?php

namespace App\Spider\Dto;

class ForumDto
{
    /** @var string */
    public $id;

    /** @var integer */
    public $page;

    /**
     * ForumDto constructor.
     *
     * @param string $id
     * @param int    $page
     */
    public function __construct(string $id, int $page = 1)
    {
        $this->id = $id;
        $this->page = $page;
    }
}
