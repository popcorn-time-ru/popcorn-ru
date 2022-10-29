<?php

namespace App\Service;

use App\Entity\BaseMedia;
use App\Entity\Movie;
use App\Entity\Show;
use DateTime;
use Symfony\Contracts\Service\Attribute\Required;
use Tmdb\Client;
use Tmdb\Exception\TmdbApiException;
use Tmdb\Model\Common\Country;
use Tmdb\Model\Common\Video;
use Tmdb\Model\Movie as TmdbMovie;
use Tmdb\Model\Network;
use Tmdb\Model\Tv as TmdbShow;
use Tmdb\Repository\MovieRepository;
use Tmdb\Repository\TvRepository;

class MediaService
{
    private const US = 'US';
    private const TYPE_TRAILER = 'Trailer';
    public const IMDB_RATING = 7.0;
    public const IMDB_COUNT = 3000;

    #[Required] public LocaleService $localeService;
    #[Required] public MovieRepository $movieRepo;
    #[Required] public TvRepository $showRepo;
    #[Required] public \App\Traktor\Client $trakt;
    #[Required] public \Tmdb\Client $client;

    public function getSeasonEpisodes(Show $show, int $season): array
    {
        $search = $this->client->getFindApi()->findBy($show->getImdb(), ['external_source' => 'imdb_id']);
        if (empty($search['tv_results'])) {
            return [];
        }
        $id = $search['tv_results'][0]['id'];

        try {
            $seasonInfo = $this->client->getTvSeasonApi()->getSeason($id, $season);
        } catch (TmdbApiException $e) {
            return [];
        }

        return $seasonInfo['episodes'];
    }

    public function getEpisodeTranslations(Show $show, int $season, int $episode): array
    {
        $search = $this->client->getFindApi()->findBy($show->getImdb(), ['external_source' => 'imdb_id']);
        $id = $search['tv_results'][0]['id'];

        try {
            $info = $this->client->getTvSeasonApi()->get(sprintf('tv/%s/season/%s/episode/%s/translations', $id, $season, $episode));
        } catch (TmdbApiException $e) {
            return [];
        }

        return $info['translations'];
    }

    public function searchMovieByTitleAndYear(string $title, int $year): ?string
    {
        $movies = $this->client->getSearchApi()->searchMovies($title, ['year' => $year]);
        if (count($movies['results']) == 1) {
            $id = $movies['results'][0]['id'];
            /** @var TmdbMovie $movieInfo */
            $movieInfo = $this->movieRepo->load($id);
            if ($movieInfo->getTitle() === $title) {
                return $movieInfo->getImdbId();
            }
            foreach ($movieInfo->getTranslations() as $translation) {
                /** @var array $data */
                $data = $translation->getData();
                $translatedTitle = $data['title'] ?? '';
                if ($translatedTitle == $title) {
                    return $movieInfo->getImdbId();
                }
            }
        }

        return null;
    }

    public function searchShowByTitle(string $title): ?string
    {
        $serials = $this->client->getSearchApi()->searchTv($title);
        if (count($serials['results']) == 1) {
            $id = $serials['results'][0]['id'];
            /** @var TmdbShow $showInfo */
            $showInfo = $this->showRepo->load($id);
            if ($showInfo->getName() === $title) {
                return $showInfo->getExternalIds()->getImdbId();
            }
            foreach ($showInfo->getTranslations() as $translation) {
                /** @var array $data */
                $data = $translation->getData();
                $translatedTitle = $data['name'] ?? '';
                if ($translatedTitle == $title) {
                    return $showInfo->getExternalIds()->getImdbId();
                }
            }
        }

        return null;
    }

    public function fetchByImdb(string $imdbId): ?BaseMedia
    {
        $search = $this->client->getFindApi()->findBy($imdbId, ['external_source' => 'imdb_id']);
        if (!empty($search['movie_results'])) {
            $id = $search['movie_results'][0]['id'];
            /** @var TmdbMovie $movieInfo */
            $movieInfo = $this->movieRepo->load($id);
            return $this->fillMovie($movieInfo, new Movie());
        }
        if (!empty($search['tv_results'])) {
            $id = $search['tv_results'][0]['id'];
            /** @var TmdbShow $showInfo */
            $showInfo = $this->showRepo->load($id);
            if (!$showInfo->getExternalIds()->getTvdbId()) {
                return null;
            }
            if (!$showInfo->getExternalIds()->getImdbId()) {
                return null;
            }
            return $this->fillShow($showInfo, new Show());
        }

        return null;
    }

    protected function fillShow(TmdbShow $showInfo, Show $show): Show
    {
        $show
            ->setImdb($showInfo->getExternalIds()->getImdbId())
            ->setTmdb($showInfo->getId())
            ->setTvdb($showInfo->getExternalIds()->getTvdbId() ?? $showInfo->getExternalIds()->getImdbId())
            ->setTitle($showInfo->getOriginalName())
            ->setYear($showInfo->getFirstAirDate() ? $showInfo->getFirstAirDate()->format('Y') : '')
            ->setSynopsis($showInfo->getOverview())
            ->setAirDay('') // TODO: инфы нет
            ->setAirTime('') // TODO: инфы нет
            ->setStatus($showInfo->getStatus())
            ->setOrigLang($showInfo->getOriginalLanguage())
            ->setNumSeasons($showInfo->getNumberOfSeasons())
            ->setLastUpdated($showInfo->getLastAirDate())
        ;
        /** @var Country $country */
        $country = current($showInfo->getOriginCountry()->toArray());
        /** @var Network $network */
        $network = current($showInfo->getNetworks()->toArray());
        $show
            ->setCountry($country ? $country->getIso31661() : '')
            ->setNetwork($network ? $network->getName() : '')
        ;
        $show->setRuntime((string)current($showInfo->getEpisodeRunTime()));

        $slug = preg_replace('#[^a-zA-Z0-9 \-]#', '', $showInfo->getName());
        $slug = preg_replace('#[\s]#', '-', $slug);
        $slug = strtolower($slug);
        $show->setSlug($slug);

        $this->fillRating($show, $showInfo);
        $this->fillImagesGenres($show, $showInfo);
        $this->localeService->fillMedia($show, $showInfo);

        return $show;
    }

    protected function fillMovie(TmdbMovie $movieInfo, Movie $movie): Movie
    {
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
            ->setTmdb($movieInfo->getId())
            ->setTitle($movieInfo->getOriginalTitle())
            ->setSynopsis($movieInfo->getOverview())
            ->setReleased($movieInfo->getReleaseDate() ?: DateTime::createFromFormat('Y-m-d', '1970-01-01'))
            ->setCertification($certification)
            ->setYear($movieInfo->getReleaseDate() ? $movieInfo->getReleaseDate()->format('Y') : '')
            ->setOrigLang($movieInfo->getOriginalLanguage())
            ->setRuntime((string)$movieInfo->getRuntime())
            ->setTrailer($trailer)
        ;

        $this->fillRating($movie, $movieInfo);
        $this->fillImagesGenres($movie, $movieInfo);
        $this->localeService->fillMedia($movie, $movieInfo);

        return $movie;
    }

    public function updateMedia(BaseMedia $media)
    {
        $search = $this->client->getFindApi()->findBy($media->getImdb(), ['external_source' => 'imdb_id']);
        if (!empty($search['movie_results']) && $media instanceof Movie) {
            $id = $search['movie_results'][0]['id'];
            /** @var TmdbMovie $info */
            $info = $this->movieRepo->load($id);
            $media = $this->fillMovie($info, $media);
        }
        if (!empty($search['tv_results']) && $media instanceof Show) {
            $id = $search['tv_results'][0]['id'];
            /** @var TmdbShow $info */
            $info = $this->showRepo->load($id);
            $media = $this->fillShow($info, $media);
        }
        $media->syncTranslations();
    }

    /**
     * @param BaseMedia          $media
     * @param TmdbMovie|TmdbShow $info
     */
    private function fillRating(BaseMedia $media, TmdbShow|TmdbMovie $info): void
    {
        try {
            if ($media instanceof Movie) {
                $trakt = $this->trakt->get("movies/{$media->getImdb()}/stats");
            } elseif ($media instanceof Show) {
                $trakt = $this->trakt->get("shows/{$media->getImdb()}/stats");
            }
        } catch (\Exception $e) {
            $trakt = new \stdClass();
            $trakt->votes = $info->getVoteCount();
            $trakt->watchers = $info->getPopularity() * 10000;
        }

        $weightRating = ($info->getVoteCount() * $info->getVoteAverage() + self::IMDB_RATING * self::IMDB_COUNT)
            / ($info->getVoteCount() + self::IMDB_COUNT);

        $media->getRating()
            ->setVotes($trakt->votes)
            ->setWatchers($trakt->watchers)
            ->setPercentage($info->getVoteAverage() * 10)
            ->setPopularity($info->getPopularity())
            ->setWeightRating($weightRating)
        ;
    }

    /**
     * @param BaseMedia          $media
     * @param TmdbMovie|TmdbShow $info
     */
    private function fillImagesGenres(BaseMedia $media, TmdbShow|TmdbMovie $info): void
    {
        $media->getImages()
            ->setPoster($info->getPosterPath() ?: '')
            ->setFanart($info->getBackdropPath() ?: '')
            ->setBanner($info->getPosterPath() ?: '')
        ;

        $genres = [];
        foreach($info->getGenres()->getGenres() as $genre) {
            $genres[] = strtolower($genre->getName());
        }
        $genres = $genres ?: ['unknown'];
        $media->setGenres($genres);
    }
}
