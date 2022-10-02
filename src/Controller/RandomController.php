<?php

namespace App\Controller;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Request\LocaleRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RandomController extends AbstractController
{
    /** @required */
    public MovieRepository $movieRepo;

    /** @required */
    public ShowRepository $showRepo;

    /** @required */
    public SerializerInterface $serializer;

    /**
     * @Route("/random/movie", name="random_movie")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function movie(LocaleRequest $localeParams)
    {
        $movie = $this->movieRepo->getRandom();

        $data = $this->serializer->serialize($movie, 'json', $localeParams->context('list'));

        return new Response($data, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @Route("/random/show", name="random_show")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function show(LocaleRequest $localeParams)
    {
        $show = $this->showRepo->getRandom();

        $data = $this->serializer->serialize($show, 'json', $localeParams->context('list'));

        return new Response($data, 200, ['Content-Type' => 'application/json']);
    }

}
