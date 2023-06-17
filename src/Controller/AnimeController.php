<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Repository\EpisodeRepository;
use App\Repository\MediaStatRepository;
use App\Repository\ShowRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class AnimeController extends AbstractController
{
    /** @required */
    public ShowRepository $repo;

    /** @required */
    public EpisodeRepository $episodeRepo;

    /** @required */
    public MediaStatRepository $statRepo;

    /** @required */
    public SerializerInterface $serializer;

    /**
     * @Route("/animes/stat", name="animes_stat")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
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

    /**
     * @Route("/animes/{page}", name="animes_page", requirements={"page"="\d+"})
     * @ParamConverter(name="pageParams", converter="page_params")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function page(PageRequest $pageParams, LocaleRequest $localeParams)
    {
        $shows = $this->repo->getPage($pageParams, $localeParams, true);

        $data = $this->serializer->serialize($shows, 'json', $localeParams->context('list'));

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/anime/{id}", name="anime")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function show($id, LocaleRequest $localeParams)
    {
        $show = $this->repo->findByImdb($id);
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $data = $this->serializer->serialize($show, 'json', $localeParams->context('item'));

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/anime/{id}/torrents", name="anime_torrents")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function torrents($id, LocaleRequest $localeParams)
    {
        $show = $this->repo->findByImdb($id);
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $data = $this->serializer->serialize(
            $show->getLocaleTorrents($localeParams->bestContentLocale),
            'json',
            $localeParams->context('torrents')
        );

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/anime/{id}/{season}/{episode}/torrents", name="anime_episode_torrents")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function episodeTorrents($id, $season, $episode, LocaleRequest $localeParams)
    {
        $show = $this->repo->findByImdb($id);
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $episodeItem = $this->episodeRepo->findOneByShowAndNumber($show, $season, $episode);
        if (!$episodeItem) {
            throw new NotFoundHttpException();
        }

        $data = $this->serializer->serialize(
            $episodeItem->getLocaleTorrents($localeParams->bestContentLocale),
            'json',
            $localeParams->context('torrents')
        );

        return new CacheJsonResponse($data, true);
    }
}
