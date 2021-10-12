<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Repository\MediaStatRepository;
use App\Repository\ShowRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

class ShowsController extends AbstractController
{
    const PAGE_SIZE = 50;

    /** @required */
    public ShowRepository $repo;

    /** @required */
    public MediaStatRepository $statRepo;

    /** @required */
    public SerializerInterface $serializer;

    /**
     * @Route("/shows/stat", name="shows_stat")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
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

    /**
     * @Route("/shows/{page}", name="shows_page")
     * @ParamConverter(name="pageParams", converter="page_params")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function page($page, PageRequest $pageParams, LocaleRequest $localeParams)
    {
        $shows = $this->repo->getPage($pageParams, $localeParams,
            self::PAGE_SIZE * max(0, $page - 1), self::PAGE_SIZE
        );

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($shows, 'json', $context);

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/show/{id}", name="show")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function show($id, LocaleRequest $localeParams)
    {
        $show = $this->repo->findByImdb($id);
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'item',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($show, 'json', $context);

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/show/{id}/torrents", name="show_torrents")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function torrents($id, LocaleRequest $localeParams)
    {
        $show = $this->repo->findByImdb($id);
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'torrents',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($show->getLocaleTorrents($localeParams->bestContentLocale), 'json', $context);

        return new CacheJsonResponse($data, true);
    }
}
