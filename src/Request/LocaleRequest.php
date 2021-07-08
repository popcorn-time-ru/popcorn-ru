<?php

namespace App\Request;

class LocaleRequest
{
    /** @var string */
    public $locale;

    /** @var string */
    public $bestContentLocale;

    /** @var array */
    public $contentLocales;

    /** @var bool */
    public $needLocale = false;
}
