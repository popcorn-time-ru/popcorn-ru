<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use App\Repository\MediaStatRepository;
use App\Repository\AnimeRepository;
use App\Request\LocaleRequest;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

class AnimesController extends AbstractController
{
    const PAGE_SIZE = 50;

    /** @var AnimeRepository */
    protected $repo;

    /** @var MediaStatRepository */
    private $statRepo;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(AnimeRepository $repo, MediaStatRepository $statRepo, SerializerInterface $serializer)
    {
        $this->repo = $repo;
        $this->statRepo = $statRepo;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/animes", name="animes")
     */
    public function index()
    {
        $count = $this->repo->count([]);
        $pages = ceil($count / self::PAGE_SIZE);
        $links = [];
        for($page = 1; $page <= $pages; $page++) {
            $links[] = 'animes/'.$page;
        }

        return new CacheJsonResponse($links, false);
    }

    /**
     * @Route("/animes/stat", name="animes_stat")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function stat(LocaleRequest $localeParams)
    {
        $stat = $this->statRepo->getByTypeAndLang('anime', $localeParams->contentLocale);
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
     * @Route("/animes/{page}", name="animes_page")
     * @ParamConverter(name="pageParams", converter="page_params")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function page($page, PageRequest $pageParams, LocaleRequest $localeParams)
    {
        $animes = $this->repo->getPage($pageParams, $localeParams,
            self::PAGE_SIZE * max(0, $page - 1), self::PAGE_SIZE
        );

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($animes, 'json', $context);

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/anime/{id}", name="anime")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function anime($id, LocaleRequest $localeParams)
    {
        $anime = $this->repo->findByKitsu($id);
        if (!$anime) {
            throw new NotFoundHttpException();
        }

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'item',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($anime, 'json', $context);

        return new CacheJsonResponse($data, true);
    }

    /**
     * @Route("/anime/{id}/torrents", name="anime_torrents")
     * @ParamConverter(name="localeParams", converter="locale_params")
     */
    public function torrents($id, LocaleRequest $localeParams)
    {
        $anime = $this->repo->findByKitsu($id);
        if (!$anime) {
            throw new NotFoundHttpException();
        }

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'torrents',
            'localeParams' => $localeParams,
        ];
        $data = $this->serializer->serialize($anime->getLocaleTorrents($localeParams->contentLocale), 'json', $context);

        return new CacheJsonResponse($data, true);
    }
}
