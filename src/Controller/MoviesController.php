<?php

namespace App\Controller;

use App\Repository\MediaStatRepository;
use App\Repository\MovieRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class MoviesController extends AbstractController
{
    const PAGE_SIZE = 50;

    const CACHE = 3600 * 12;

    /** @var MovieRepository */
    protected $repo;

    /** @var MediaStatRepository */
    protected $statRepo;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(MovieRepository $repo, MediaStatRepository $statRepo, SerializerInterface $serializer)
    {
        $this->repo = $repo;
        $this->statRepo = $statRepo;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/movies", name="movies")
     */
    public function index()
    {
        $count = $this->repo->count([]);
        $pages = ceil($count / self::PAGE_SIZE);
        $links = [];
        for($page = 1; $page <= $pages; $page++) {
            $links[] = 'movies/'.$page;
        }

        return $this->resp(json_encode($links, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Route("/movies/stat", name="movies_stat")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function stat(LocaleRequest $localeParams)
    {
        $stat = $this->statRepo->getByTypeAndLang('movie', $localeParams->contentLocale);
        $data = [];
        foreach ($stat as $s) {
            $data[$s->getGenre()] = [
                'count' => $s->getCount(),
                'title' => $s->getTitle(),
            ];
        }

        return (new JsonResponse($data, 200))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE)
            ->setSharedMaxAge(self::CACHE);
    }

    /**
     * @Route("/movies/{page}", name="movies_page")
     * @ParamConverter(name="pageParams", converter="page_params")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function page($page, PageRequest $pageParams, LocaleRequest $localeParams)
    {
        $movies = $this->repo->getPage($pageParams, $localeParams,
            self::PAGE_SIZE * ($page - 1), self::PAGE_SIZE
        );

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($movies, 'json', $context);

        return $this->resp($data);
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

        return $this->resp($data);
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
            'mode' => 'list',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($movie->getLocaleTorrents($localeParams->contentLocale), 'json', $context);

        return $this->resp($data);
    }

    protected function resp($data)
    {
        return (new Response($data, 200, ['Content-Type' => 'application/json']))
            ->setSharedMaxAge(self::CACHE);
    }
}
