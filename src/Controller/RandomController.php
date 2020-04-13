<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

class RandomController extends AbstractController
{
    /** @var string */
    private $defaultLocale;

    /** @var MovieRepository */
    private $movieRepo;

    /** @var ShowRepository */
    private $showRepo;

    public function __construct(MovieRepository $movieRepo, ShowRepository $showRepo, string $defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
        $this->movieRepo = $movieRepo;
        $this->showRepo = $showRepo;
    }

    /**
     * @Route("/random/movie", name="random_movie")
     */
    public function movie(Request $r, SerializerInterface $serializer)
    {
        $movie = $this->movieRepo->getRandom();

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'locale' => $r->query->get('locale', $this->defaultLocale),
        ];
        $data = $serializer->serialize($movie, 'json', $context);

        return new Response($data, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route("/random/show", name="random_show")
     */
    public function show(Request $r, SerializerInterface $serializer)
    {
        $show = $this->showRepo->getRandom();

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'locale' => $r->query->get('locale', $this->defaultLocale),
        ];
        $data = $serializer->serialize($show, 'json', $context);

        return new Response($data, 200, ['Content-Type' => 'application/json']);
    }

}
