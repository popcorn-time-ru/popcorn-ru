<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class MoviesController extends AbstractController
{
    const PAGE_SIZE = 50;

    const CACHE = 3600 * 12;
    /**
     * @var MovieRepository
     */
    protected $repo;

    public function __construct(MovieRepository $repo)
    {
        $this->repo = $repo;
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
     * @Route("/movies/{page}", name="movies_page")
     * @ParamConverter(name="pageParams", converter="page_params")
     */
    public function page($page, Request $r, PageRequest $pageParams, SerializerInterface $serializer)
    {
        $movies = $this->repo->getPage($pageParams,
            self::PAGE_SIZE * ($page - 1), self::PAGE_SIZE
        );

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'locale' => $pageParams->locale,
        ];
        $data = $serializer->serialize($movies, 'json', $context);

        return $this->resp($data);
    }

    /**
     * @Route("/movie/{id}", name="movie")
     */
    public function movie($id, Request $r, SerializerInterface $serializer)
    {
        $movie = $this->repo->findByImdb($id);

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'item',
            'locale' => $r->query->get('locale', ''),
        ];
        $data = $serializer->serialize($movie, 'json', $context);

        return $this->resp($data);
    }

    protected function resp($data)
    {
        return (new Response($data, 200, ['Content-Type' => 'application/json']))
            ->setSharedMaxAge(self::CACHE);
    }
}
