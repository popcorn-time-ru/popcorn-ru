<?php

namespace App\Spider\Dto;

class TopicDto
{
    /** @var string */
    public $id;

    /** @var integer */
    public $seed;

    /** @var integer */
    public $leech;

    /** @var integer */
    public $delay;

    /**
     * TopicDto constructor.
     *
     * @param string $id
     * @param int    $seed
     * @param int    $leech
     * @param int    $delay
     */
    public function __construct(string $id, int $seed, int $leech, int $delay = 0)
    {
        $this->id = $id;
        $this->seed = $seed;
        $this->leech = $leech;
        $this->delay = $delay;
    }
}
