<?php

namespace App\Service\Search;

use App\Request\PageRequest;

interface SearchInterface
{
    public function search($qb, $class, PageRequest $pageRequest, string $locale);
}
