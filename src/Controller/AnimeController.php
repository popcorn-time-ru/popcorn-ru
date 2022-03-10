<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AnimeController extends AbstractController
{
    /**
     * @Route("/animes/stat", name="animes_stat")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function stat(LocaleRequest $localeParams)
    {
        $data = [
            'all' => [
                'count' => 0,
                'title' => 'All',
            ]
        ];

        return new CacheJsonResponse($data, false);
    }

    /**
     * @Route("/animes/{page}", name="animes_page", requirements={"page"="\d+"})
     * @ParamConverter(name="pageParams", converter="page_params")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function page(PageRequest $pageParams, LocaleRequest $localeParams)
    {
        return new CacheJsonResponse([]);
    }

}
