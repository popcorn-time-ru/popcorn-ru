<?php

namespace App\Controller;

use App\HttpFoundation\CacheJsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route(path="/status")
     */
    public function status()
    {
        $data = [
            'repo' => 'https://github.com/popcorn-time-ru/popcorn-ru',
            'server' => 'serv01',
            'status' => 'idle',
            'totalAnimes' => 0,
            'totalMovies' => 70000,
            'totalShows' => 9000,
            'updated' => time(),
            'uptime' => 100000,
            'version' => '0.0.1',
            'commit' => 'ac462477',
        ];

        return new CacheJsonResponse($data, false);
    }
}
