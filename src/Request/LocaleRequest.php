<?php

namespace App\Request;

class LocaleRequest
{
    /** @var string */
    public $locale;

    /** @var string */
    public $contentLocale;

    /** @var bool */
    public $needLocale = false;
}
