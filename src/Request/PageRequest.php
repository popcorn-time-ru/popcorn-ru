<?php

namespace App\Request;

class PageRequest
{
    /** @var string */
    public $locale;

    /** @var string */
    public $genre;

    /** @var string */
    public $keywords;

    /** @var string */
    public $sort;

    /** @var string */
    public $order;
}
