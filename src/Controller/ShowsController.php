<?php

namespace App\Controller;

use App\Repository\ShowRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

class ShowsController extends AbstractController
{
    const PAGE_SIZE = 50;

    const CACHE = 3600 * 12;
    /**
     * @var ShowRepository
     */
    protected $repo;

    public function __construct(ShowRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @Route("/shows", name="shows")
     */
    public function index()
    {
        $count = $this->repo->count([]);
        $pages = ceil($count / self::PAGE_SIZE);
        $links = [];
        for($page = 1; $page <= $pages; $page++) {
            $links[] = 'shows/'.$page;
        }

        return $this->resp(json_encode($links, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @Route("/shows/{page}", name="shows_page")
     */
    public function page($page, Request $r, SerializerInterface $serializer)
    {
        $sort = $r->query->get('sort', '');
        $order = (int) $r->query->get('order', -1);

        $genre = $r->query->get('genre', 'all');
        $genre = strtolower($genre);
        if (preg_match('/science[-\s]fuction/i', $genre) || preg_match('/sci[-\s]fi/i', $genre)) {
            $genre = 'science-fiction';
        }

        $keywords = $r->query->get('keywords', '');

        $shows = $this->repo->getPage(
            $genre, $keywords,
            $sort, $order > 0 ? 'ASC' : 'DESC',
            self::PAGE_SIZE * ($page - 1), self::PAGE_SIZE
        );

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'locale' => $r->query->get('locale', ''),
        ];
        $data = $serializer->serialize($shows, 'json', $context);

        return $this->resp($data);
    }

    /**
     * @Route("/show/{id}", name="show")
     */
    public function show($id, Request $r, SerializerInterface $serializer)
    {
        $show = $this->repo->findByImdb($id);

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'item',
            'locale' => $r->query->get('locale', ''),
        ];
        $data = $serializer->serialize($show, 'json', $context);

        return $this->resp($data);
    }

    protected function resp($data)
    {
        return (new Response($data, 200, ['Content-Type' => 'application/json']))
            ->setSharedMaxAge(self::CACHE);
    }
}
