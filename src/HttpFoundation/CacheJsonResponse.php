<?php

namespace App\HttpFoundation;

use Symfony\Component\HttpFoundation\JsonResponse;

class CacheJsonResponse extends JsonResponse
{
    const CACHE_TIME_MIN = 3600 * 10;
    const CACHE_TIME_MAX = 3600 * 12;

    public function __construct($data = null, bool $json = false)
    {
        $this->encodingOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        parent::__construct($data, 200, [], $json);
        $this->setSharedMaxAge(random_int(self::CACHE_TIME_MIN, self::CACHE_TIME_MAX));
    }
}
