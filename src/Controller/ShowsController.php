<?php

namespace App\Controller;

use App\Entity\Show;
use App\Repository\MediaStatRepository;
use App\Repository\ShowRepository;
use App\Request\PageRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

class ShowsController extends AbstractController
{
    const PAGE_SIZE = 50;

    const CACHE = 3600 * 12;

    /** @var ShowRepository */
    protected $repo;

    /** @var MediaStatRepository */
    private $statRepo;

    /** @var string */
    private $defaultLocale;

    public function __construct(ShowRepository $repo, MediaStatRepository $statRepo, string $defaultLocale)
    {
        $this->repo = $repo;
        $this->defaultLocale = $defaultLocale;
        $this->statRepo = $statRepo;
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
     * @Route("/shows/stat", name="shows_stat")
     */
    public function stat(Request $r)
    {
        $locale = $r->query->get('locale', $this->defaultLocale);
        $stat = $this->statRepo->getByTypeAndLang('show', $locale);
        $data = [];
        foreach ($stat as $s) {
            $data[$s->getGenre()] = [
                'count' => $s->getCount(),
                'title' => $s->getTitle(),
            ];
        }

        return (new JsonResponse($data, 200))
            ->setEncodingOptions(JSON_UNESCAPED_UNICODE)
            ->setSharedMaxAge(self::CACHE);
    }

    /**
     * @Route("/shows/{page}", name="shows_page")
     * @ParamConverter(name="pageParams", converter="page_params")
     */
    public function page($page, Request $r, PageRequest $pageParams, SerializerInterface $serializer)
    {
        $shows = $this->repo->getPage($pageParams,
            self::PAGE_SIZE * ($page - 1), self::PAGE_SIZE
        );

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
            'locale' => $pageParams->locale,
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
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'item',
            'locale' => $r->query->get('locale', $this->defaultLocale),
        ];
        $data = $serializer->serialize($show, 'json', $context);

        return $this->resp($data);
    }


    /**
     * @Route("/show/{id}/torrents", name="show_torrents")
     */
    public function torrents($id, Request $r, SerializerInterface $serializer)
    {
        $show = $this->repo->findByImdb($id);
        if (!$show) {
            throw new NotFoundHttpException();
        }

        $locale = $r->query->get('locale', $this->defaultLocale);
        $context = [
            JsonEncode::OPTIONS => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            'mode' => 'list',
        ];
        $data = $serializer->serialize($show->getLocaleTorrents($locale), 'json', $context);

        return $this->resp($data);
    }

    protected function resp($data)
    {
        return (new Response($data, 200, ['Content-Type' => 'application/json']))
            ->setSharedMaxAge(self::CACHE);
    }
}
