<?php

namespace App\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class PageParamConverter implements ParamConverterInterface
{
    private const PAGE_SIZE = 50;

    public function apply(Request $request, ParamConverter $configuration)
    {
        $pageRequest = new PageRequest();

        $pageRequest->limit = $request->query->get('limit') ?: self::PAGE_SIZE;
        $page = $request->attributes->get('page');
        $page = max(0, $page - 1);
        $pageRequest->offset = $page * $pageRequest->limit;

        $genre = $request->query->get('genre', 'all');
        $genre = strtolower($genre);
        $pageRequest->genre = $genre === 'all' ? '' : $genre;
        $pageRequest->keywords = trim($request->query->get('keywords', ''));
        $pageRequest->keywords = str_replace('%', '', $pageRequest->keywords);
        $pageRequest->sort = $request->query->get('sort', '');
        $order = (int)$request->query->get('order', -1);
        $pageRequest->order = $order > 0 ? 'ASC' : 'DESC';

        $request->attributes->set($configuration->getName(), $pageRequest);

        return true;
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getConverter() === 'page_params' && $configuration->getClass() === PageRequest::class;
    }
}
