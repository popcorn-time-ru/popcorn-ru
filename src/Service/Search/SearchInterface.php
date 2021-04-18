<?php

namespace App\Service\Search;

use App\Request\PageRequest;
use Doctrine\ORM\Mapping\ClassMetadata;

interface SearchInterface
{
    public function search($qb, ClassMetadata $class, PageRequest $pageRequest, string $locale);
}
