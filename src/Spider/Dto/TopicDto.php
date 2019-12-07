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

    /**
     * TopicDto constructor.
     *
     * @param string $id
     * @param int    $seed
     * @param int    $leech
     */
    public function __construct(string $id, int $seed, int $leech)
    {
        $this->id = $id;
        $this->seed = $seed;
        $this->leech = $leech;
    }
}
