<?php

namespace App\HttpFoundation;

use Symfony\Component\HttpFoundation\JsonResponse;

class CacheJsonResponse extends JsonResponse
{
    const CACHE_TIME = 3600 * 12;

    public function __construct($data = null, bool $json = false)
    {
        $this->encodingOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        parent::__construct($data, 200, [], $json);
        $this->setSharedMaxAge(self::CACHE_TIME);
    }
}
