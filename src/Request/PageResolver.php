<?php

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class PageResolver implements ValueResolverInterface
{
    private const PAGE_SIZE = 50;
    private const PAGE_SIZE_MAX = 100;

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (
            $argumentType != PageRequest::class
            && !is_subclass_of($argumentType, PageRequest::class, true)
        ) {
            return [];
        }

        $pageRequest = new PageRequest();

        $pageRequest->limit = min($request->query->get('limit', self::PAGE_SIZE), self::PAGE_SIZE_MAX);
        $page = $request->attributes->get('page');
        $page = max(0, $page - 1);
        $pageRequest->offset = $page * $pageRequest->limit;

        $genre = $request->query->get('genre', 'all');
        $genre = strtolower($genre);
        $pageRequest->genre = $genre === 'all' ? '' : $genre;
        $pageRequest->keywords = trim($request->query->get('keywords', ''));
        $pageRequest->keywords = str_replace('%', '', $pageRequest->keywords);
        $pageRequest->sort = $request->query->get('sort', '');
        $order = (int) $request->query->get('order', -1);
        $pageRequest->order = $order > 0 ? 'ASC' : 'DESC';

        return [$pageRequest];
    }
}
