<?php

namespace App\Service\Search;

use App\Request\PageRequest;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

interface SearchInterface
{
    public function search(QueryBuilder $qb, ClassMetadata $class, PageRequest $pageRequest, string $locale, int $offset, int $limit): QueryBuilder;
}
