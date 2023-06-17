<?php

namespace App\Service\Search;

use App\Entity\BaseMedia;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

interface SearchInterface
{
    /**
     * @return BaseMedia[]
     */
    public function search(
        QueryBuilder $qb,
        ClassMetadata $class,
        PageRequest $pageRequest,
        LocaleRequest $localeParams,
        int $offset,
        int $limit,
        bool $anime
    ): array;
}
