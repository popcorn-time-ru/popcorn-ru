<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Repository\MediaStatRepository;
use App\Repository\MovieRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

class MoviesController extends AbstractController
{
    const PAGE_SIZE = 50;

    /** @required */
    public MovieRepository $repo;

    /** @required */
    public MediaStatRepository $statRepo;

    /** @required */
    public SerializerInterface $serializer;

    /**
     * @Route("/movies/stat", name="movies_stat")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function stat(LocaleRequest $localeParams)
    {
        $stat = $this->statRepo->getByTypeAndLang('movie', $localeParams->bestContentLocale);
        $data = [];
        foreach ($stat as $s) {
            $count = $localeParams->contentLocales ? $s->getCountLang() : $s->getCountAll();
            if (!$count) {
                continue;
            }
            $data[$s->getGenre()] = [
                'count' => $count,
                'title' => $s->getTitle(),
            ];
        }

        return new CacheJsonResponse($data, false);
    }

    /**
     * @Route("/movies/{page}", name="movies_page")
     * @ParamConverter(name="pageParams", converter="page_params")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function page(PageRequest $pageParams, LocaleRequest $localeParams)
    {
        $movies = $this->repo->getPage($pageParams, $localeParams);

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($movies, 'json', $context);

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/movie/{id}", name="movie")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function movie($id, LocaleRequest $localeParams)
    {
        $movie = $this->repo->findByImdb($id);
        if (!$movie) {
            throw new NotFoundHttpException();
        }

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'item',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($movie, 'json', $context);

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/movie/{id}/torrents", name="movie_torrents")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function torrents($id, LocaleRequest $localeParams)
    {
        $movie = $this->repo->findByImdb($id);
        if (!$movie) {
            throw new NotFoundHttpException();
        }

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'torrents',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($movie->getLocaleTorrents($localeParams->bestContentLocale), 'json', $context);

        return new CacheJsonResponse($data, true);
    }
}
