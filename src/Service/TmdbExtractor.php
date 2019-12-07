<?php

namespace App\Service;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use Tmdb\Client;
use Tmdb\Model\Common\Country;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Common\Video;
use Tmdb\Model\Movie as TmdbMovie;
use Tmdb\Model\Network;
use Tmdb\Model\Tv as TmdbShow;
use Tmdb\Repository\MovieRepository;
use Tmdb\Repository\TvRepository;

class TmdbExtractor
{
    private const US = 'US';
    private const LOCALE = 'en';
    private const TYPE_TRAILER = 'Trailer';
    private const IMAGE_BASE = 'http://image.tmdb.org/t/p/w500';

    private const SYNC_TIMEOUT = 3600 * 24 * 7;

    /** @var MovieRepository */
    protected $movieRepo;

    /** @var TvRepository */
    protected $showRepo;

    /** @var Client */
    private $client;

    public function __construct(
        Client $client,
        MovieRepository $movieRepo,
        TvRepository $showRepo
    )
    {
        $this->movieRepo = $movieRepo;
        $this->showRepo = $showRepo;
        $this->client = $client;
    }

    public function getSeasonEpisodes(Show $show, int $season): array
    {
        $search = $this->client->getFindApi()->findBy($show->getImdb(), ['external_source' => 'imdb_id']);
        $id = $search['tv_results'][0]['id'];

        $seasonInfo = $this->client->getTvSeasonApi()->getSeason($id, $season);

        return $seasonInfo['episodes'];
    }

    public function fetchByImdb(string $imdbId): ?BaseMedia
    {
        $search = $this->client->getFindApi()->findBy($imdbId, ['external_source' => 'imdb_id']);
        if (!empty($search['movie_results'])) {
            $id = $search['movie_results'][0]['id'];
            return $this->getMovie($id);
        }
        if (!empty($search['tv_results'])) {
            $id = $search['tv_results'][0]['id'];
            return $this->getShow($id);
        }

        return null;
    }

    protected function getShow(int $id): ?Show
    {
        /** @var TmdbShow $showInfo */
        $showInfo = $this->showRepo->load($id);
        if (!$showInfo->getExternalIds()->getTvdbId()) {
            return null;
        }

        $show = new Show();
        $show
            ->setImdb($showInfo->getExternalIds()->getImdbId())
            ->setTvdb($showInfo->getExternalIds()->getTvdbId())
            ->setTitle($showInfo->getOriginalName())
            ->setYear($showInfo->getFirstAirDate()->format('Y'))
            ->setSynopsis($showInfo->getOverview())
            ->setAirDay('') // TODO: инфы нет
            ->setAirTime('') // TODO: инфы нет
            ->setStatus($showInfo->getStatus())
            ->setNumSeasons($showInfo->getNumberOfSeasons())
            ->setLastUpdated($showInfo->getLastAirDate()->getTimestamp())
        ;
        /** @var Country $country */
        $country = current($showInfo->getOriginCountry()->toArray());
        /** @var Network $network */
        $network = current(current($showInfo->getNetworks()));
        $show
            ->setCountry($country->getIso31661())
            ->setNetwork($network->getName())
        ;
        $show->setRuntime((string)current($showInfo->getEpisodeRunTime()));

        $slug = preg_replace('#[^a-zA-Z0-9 \-]#', '', $showInfo->getName());
        $slug = preg_replace('#[\s]#', '-', $slug);
        $slug = strtolower($slug);
        $show->setSlug($slug);

        $this->fillRatingImagesGenres($show, $showInfo);

        return $show;
    }

    protected function getMovie(int $id): ?Movie
    {

        /** @var TmdbMovie $movieInfo */
        $movieInfo = $this->movieRepo->load($id);

        $movie = new Movie();

        $certification = '';
        foreach($movieInfo->getReleaseDates() as $release) {
            if ($release->getIso31661() == self::US) {
                $certification = $release->getCertification();
            }
        }

        $trailer = '';
        foreach ($movieInfo->getVideos()->getVideos() as $video) {
            /** @var Video $video */
            if ($video->getType() == self::TYPE_TRAILER) {
                $trailer = $video->getUrl();
                break;
            }
        }

        $movie
            ->setImdb($movieInfo->getImdbId())
            ->setTitle($movieInfo->getTitle())
            ->setSynopsis($movieInfo->getOverview())
            ->setReleased($movieInfo->getReleaseDate()->getTimestamp())
            ->setCertification($certification)
            ->setYear($movieInfo->getReleaseDate()->format('Y'))
            ->setRuntime((string)$movieInfo->getRuntime())
            ->setTrailer($trailer)
        ;

        $this->fillRatingImagesGenres($movie, $movieInfo);

        return $movie;
    }

    /**
     * @param BaseMedia $media
     * @param TmdbShow|TmdbMovie $info
     */
    private function fillRatingImagesGenres(BaseMedia $media, $info): void
    {
        $media->getImages()
            ->setPoster(self::IMAGE_BASE . $info->getPosterPath())
            ->setFanart(self::IMAGE_BASE . $info->getBackdropPath())
            ->setBanner(self::IMAGE_BASE . $info->getPosterPath())
        ;

        $media->getRating()
            ->setVotes($info->getVoteCount())
            ->setWatching($info->getPopularity() * 10000)
            ->setPercentage($info->getVoteAverage() * 10)
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

        $genres = [];
        foreach($info->getGenres()->getGenres() as $genre) {
            $genres[] = strtolower($genre->getName());
        }
        $genres = $genres ?: ['unknown'];
        $media->setGenres($genres);
    }
}
