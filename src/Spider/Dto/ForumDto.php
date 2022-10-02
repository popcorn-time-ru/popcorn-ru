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

    /** @var string|null */
    public $last;

    /**
     * ForumDto constructor.
     *
     * @param string      $id
     * @param int         $page
     * @param string|null $last
     * @param int         $delay
     */
    public function __construct(string $id, int $page = 1, ?string $last = null, int $delay = 0)
    {
        $this->id = $id;
        $this->page = $page;
        $this->last = $last;
        $this->delay = $delay;
    }
}
