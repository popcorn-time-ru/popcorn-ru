<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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

        return $this->resp($links);
    }

    /**
     * @Route("/movies/{page}", name="movies_page")
     */
    public function page($page, Request $r)
    {
        $sort = $r->query->get('sort', '');
        $order = (int) $r->query->get('order', -1);

        $genre = $r->query->get('genre', 'all');
        $genre = strtolower($genre);
        if (preg_match('/science[-\s]fuction/i', $genre) || preg_match('/sci[-\s]fi/i', $genre)) {
            $genre = 'science-fiction';
        }

        $keywords = $r->query->get('keywords', '');

        $movies = $this->repo->getPage(
            $genre, $keywords,
            $sort, $order > 0 ? 'ASC' : 'DESC',
            self::PAGE_SIZE * ($page - 1), self::PAGE_SIZE
        );

        return $this->resp($movies);
    }

    /**
     * @Route("/movie/{id}", name="movie")
     */
    public function movie($id)
    {
        $movie = $this->repo->findByImdb($id);
        return $this->resp($movie);
    }

    protected function resp($data)
    {
        return $this->json($data)
            ->setEncodingOptions(JSON_UNESCAPED_SLASHES)
            ->setSharedMaxAge(self::CACHE);
    }
}
