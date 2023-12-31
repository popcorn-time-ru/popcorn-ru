<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Request\LocaleRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class RandomController extends AbstractController
{
    #[Required] public MovieRepository $movieRepo;
    #[Required] public ShowRepository $showRepo;
    #[Required] public SerializerInterface $serializer;

    #[Route("/random/movie", name: "random_movie")]
    public function movie(LocaleRequest $localeParams)
    {
        $movie = $this->movieRepo->getRandom();

        $data = $this->serializer->serialize($movie, 'json', $localeParams->context('list'));

        return new Response($data, 200, ['Content-Type' => 'application/json']);
    }

    #[Route("/random/show", name: "random_show")]
    public function show(LocaleRequest $localeParams)
    {
        $show = $this->showRepo->getRandom();

        $data = $this->serializer->serialize($show, 'json', $localeParams->context('list'));

        return new Response($data, 200, ['Content-Type' => 'application/json']);
    }

}
