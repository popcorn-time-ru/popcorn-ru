<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AnimeController extends AbstractController
{
    #[Route("/animes/stat", name: "animes_stat")]
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

    #[Route("/animes/{page}", name: "animes_page", requirements: ["page" => "\d+"])]
    public function page(PageRequest $pageParams, LocaleRequest $localeParams)
    {
        return new CacheJsonResponse([]);
    }
}
