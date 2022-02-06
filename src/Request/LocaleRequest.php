<?php

namespace App\Request;

use Symfony\Component\Serializer\Encoder\JsonEncode;

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

    public function context(string $mode): array
    {
        return [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => $mode,
            'localeParams' => $this,
        ];
    }
}
