<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Repository\MediaStatRepository;
use App\Repository\MovieRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class MoviesController extends AbstractController
{
    #[Required] public MovieRepository $repo;
    #[Required] public MediaStatRepository $statRepo;
    #[Required] public SerializerInterface $serializer;

    #[Route("/movies/stat", name: "movies_stat")]
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

    #[Route("/movies/{page}", name: "movies_page", requirements: ["page" => "\d+"])]
    public function page(PageRequest $pageParams, LocaleRequest $localeParams)
    {
        $movies = $this->repo->getPage($pageParams, $localeParams);

        $data = $this->serializer->serialize($movies, 'json', $localeParams->context('list'));

        return new CacheJsonResponse($data, true);
    }

    #[Route("/movie/{id}", name: "movie")]
    public function movie($id, LocaleRequest $localeParams)
    {
        $movie = $this->repo->findByImdb($id);
        if (!$movie) {
            throw new NotFoundHttpException();
        }

        $data = $this->serializer->serialize($movie, 'json', $localeParams->context('item'));

        return new CacheJsonResponse($data, true);
    }

    #[Route("/movie/{id}/torrents", name: "movie_torrents")]
    public function torrents($id, LocaleRequest $localeParams)
    {
        $movie = $this->repo->findByImdb($id);
        if (!$movie) {
            throw new NotFoundHttpException();
        }

        $data = $this->serializer->serialize(
            $movie->getLocaleTorrents($localeParams->bestContentLocale),
            'json',
            $localeParams->context('torrents')
        );

        return new CacheJsonResponse($data, true);
    }
}
