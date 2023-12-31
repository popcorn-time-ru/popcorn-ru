<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Repository\EpisodeRepository;
use App\Repository\MediaStatRepository;
use App\Repository\ShowRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class ShowsController extends AbstractController
{
    #[Required] public ShowRepository $repo;
    #[Required] public EpisodeRepository $episodeRepo;
    #[Required] public MediaStatRepository $statRepo;
    #[Required] public SerializerInterface $serializer;

    #[Route("/shows/stat", name: "shows_stat")]
    public function stat(LocaleRequest $localeParams)
    {
        $stat = $this->statRepo->getByTypeAndLang('show', $localeParams->bestContentLocale);
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

    #[Route("/shows/{page}", name: "shows_page", requirements: ["page" => "\d+"])]
    public function page(PageRequest $pageParams, LocaleRequest $localeParams, Request $r)
    {
        $shows = $this->repo->getPage($pageParams, $localeParams, (bool)$r->get('anime'));

        $data = $this->serializer->serialize($shows, 'json', $localeParams->context('list'));

        return new CacheJsonResponse($data, true);
    }

    #[Route("/show/{id}", name: "show")]
    public function show($id, LocaleRequest $localeParams)
    {
        $show = $this->repo->findByImdb($id);
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $data = $this->serializer->serialize($show, 'json', $localeParams->context('item'));

        return new CacheJsonResponse($data, true);
    }

    #[Route("/show/{id}/torrents", name: "show_torrents")]
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

    #[Route("/show/{id}/{season}/{episode}/torrents", name: "show_episode_torrents")]
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
