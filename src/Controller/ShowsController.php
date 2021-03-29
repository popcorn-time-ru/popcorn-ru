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

    /** @var ShowRepository */
    protected $repo;

    /** @var MediaStatRepository */
    private $statRepo;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(ShowRepository $repo, MediaStatRepository $statRepo, SerializerInterface $serializer)
    {
        $this->repo = $repo;
        $this->statRepo = $statRepo;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/shows/stat", name="shows_stat")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function stat(LocaleRequest $localeParams)
    {
        $stat = $this->statRepo->getByTypeAndLang('show', $localeParams->contentLocale);
        $data = [];
        foreach ($stat as $s) {
            $data[$s->getGenre()] = [
                'count' => $s->getCount(),
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
            self::PAGE_SIZE * ($page - 1), self::PAGE_SIZE
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
        $data = $this->serializer->serialize($show->getLocaleTorrents($localeParams->contentLocale), 'json', $context);

        return new CacheJsonResponse($data, true);
    }
}
