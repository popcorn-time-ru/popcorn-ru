<?php

namespace App\Service;

use App\Repository\MovieRepository;
use Tmdb\Model\Common\Video;
use Tmdb\Model\Image;
use Tmdb\Model\Movie as TmdbMovie;
use Tmdb\Repository\MovieRepository as TmdbApi;

class MovieInfo
{
    private const US = 'US';
    private const LOCALE = 'en';
    private const TYPE_TRAILER = 'Trailer';
    private const IMAGE_BASE = 'http://image.tmdb.org/t/p/w500';

    private const SYNC_TIMEOUT = 3600 * 24 * 7;

    /**
     * @var TmdbApi
     */
    protected $tmdb;

    /**
     * @var MovieRepository
     */
    protected $repo;

    public function __construct(TmdbApi $tmdb, MovieRepository $repo)
    {
        $this->tmdb = $tmdb;
        $this->repo = $repo;
    }

    public function fetchToLocal(string $imdbId): void
    {
        $movie = $this->repo->findOrCreateByImdb($imdbId);

        if ($movie->synced(self::SYNC_TIMEOUT)) {
            return;
        }

        /** @var TmdbMovie $movieInfo */
        $movieInfo = $this->tmdb->load($imdbId);

        $certification = '';
        foreach($movieInfo->getReleaseDates() as $release) {
            if ($release->getIso31661() == self::US) {
                $certification = $release->getCertification();
            }
        }
        $genres = [];
        foreach($movieInfo->getGenres()->getGenres() as $genre) {
            $genres[] = strtolower($genre->getName());
        }
        $genres = $genres ?: ['unknown'];

        $trailer = '';
        foreach ($movieInfo->getVideos()->getVideos() as $video) {
            /** @var Video $video */
            if ($video->getType() == self::TYPE_TRAILER) {
                $trailer = $video->getUrl();
                break;
            }
        }

        $movie
            ->setTitle($movieInfo->getTitle())
            ->setSynopsis($movieInfo->getOverview())
            ->setReleased($movieInfo->getReleaseDate()->getTimestamp())
            ->setCertification($certification)
            ->setYear($movieInfo->getReleaseDate()->format('Y'))
            ->setGenres($genres)
            ->setRuntime((string)$movieInfo->getRuntime())
            ->setTrailer($trailer)
        ;

        // $poster = ''; $posterRate = 0;
        // $fanart = ''; $fanartRate = 0;
        // foreach ($movieInfo->getImages()->getImages() as $img) {
        //     /** @var Image $img */
        //     if ($img->getIso6391() && $img->getIso6391() != self::LOCALE) {
        //         continue;
        //     }
        //     if ($img instanceof Image\PosterImage) {
        //         if ($img->getVoteAverage() > $posterRate) {
        //             $posterRate = $img->getVoteAverage();
        //             $poster = self::IMAGE_BASE . $img->getFilePath();
        //         }
        //     }
        //     if ($img instanceof Image\BackdropImage) {
        //         if ($img->getVoteAverage() > $fanartRate) {
        //             $fanartRate = $img->getVoteAverage();
        //             $fanart = self::IMAGE_BASE . $img->getFilePath();
        //         }
        //     }
        // }

        $movie->getImages()
            ->setPoster(self::IMAGE_BASE . $movieInfo->getPosterPath())
            ->setFanart(self::IMAGE_BASE . $movieInfo->getBackdropPath())
            ->setBanner(self::IMAGE_BASE . $movieInfo->getPosterPath())
        ;

        $movie->getRating()
            ->setVotes($movieInfo->getVoteCount())
            ->setWatching($movieInfo->getPopularity() * 10000)
            ->setPercentage($movieInfo->getVoteAverage() * 10)
        ;

        $movie->sync();
        $this->repo->flush();
    }
}
