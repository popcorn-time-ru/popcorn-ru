<?php

namespace App\Service;

use App\Repository\MediaStatRepository;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use Prometheus\CollectorRegistry;
use Symfony\Contracts\Service\Attribute\Required;
use Tmdb\Repository\GenreRepository;

class StatService
{
    #[Required] public MovieRepository $movieRepo;
    #[Required] public ShowRepository $showRepo;
    #[Required] public TorrentRepository $torrent;
    #[Required] public MediaStatRepository $statRepo;
    #[Required] public GenreRepository $genresRepo;
    #[Required] public CollectorRegistry $cr;
    private $keyToNum = [];
    private $numToTranslated = [];

    public function calculateTorrentStat()
    {
        $torrentProm = $this->cr->getOrRegisterGauge('popcorn', 'torrent', 'torrents count', ['provider']);
        foreach ($this->torrent->getStatByProvider() as $provider => $count) {
            $torrentProm->set($count, [$provider]);
        }
    }

    public function calculateMediaStat()
    {
        $movieProm = $this->cr->getOrRegisterGauge('popcorn', 'movies', 'movies count', ['lang', 'genre']);
        $showProm = $this->cr->getOrRegisterGauge('popcorn', 'shows', 'shows count', ['lang', 'genre']);

        $movieStat = $this->groupGenreStat($this->movieRepo->getGenreStatistics(), $this->movieRepo->getGenreLangStatistics());
        $showStat = $this->groupGenreStat($this->showRepo->getGenreStatistics(), $this->showRepo->getGenreLangStatistics());

        foreach ($movieStat as $lang => $langStat) {
            foreach ($langStat as $genre => $count) {
                if ($genre === 'unknown') {
                    continue;
                }
                $stat = $this->statRepo->getOrCreate('movie', $genre, $lang);
                if (empty($stat->getTitle())) {
                    $stat->setTitle($this->translatedTitle($genre, $lang));
                }
                $stat->setCountLang($count);
                $stat->setCountAll($movieStat['all'][$genre]);
                $movieProm->set($count, [$lang, $genre]);
            }
        }

        foreach ($showStat as $lang => $langStat) {
            foreach ($langStat as $genre => $count) {
                if ($genre === 'unknown') {
                    continue;
                }
                $stat = $this->statRepo->getOrCreate('show', $genre, $lang);
                if (empty($stat->getTitle())) {
                    $stat->setTitle($this->translatedTitle($genre, $lang));
                }
                $stat->setCountLang($count);
                $stat->setCountAll($showStat['all'][$genre]);
                $showProm->set($count, [$lang, $genre]);
            }
        }
        $this->statRepo->flush();
    }

    private function groupGenreStat(array $allRows, array $langRows): array
    {
        $group = [];
        foreach ($allRows as $stat) {
            foreach ($stat['genres'] as $genre) {
                if (empty($group['all'][$genre])) {
                    $group['all'][$genre] = 0;
                }
                $group['all'][$genre] += $stat['c'];
            }
            if (empty($group['all']['all'])) {
                $group['all']['all'] = 0;
            }
            $group['all']['all'] += $stat['c'];
        }
        foreach ($langRows as $stat) {
            foreach ($stat['existTranslations'] as $lang) {
                foreach ($stat['genres'] as $genre) {
                    if (empty($group[$lang][$genre])) {
                        $group[$lang][$genre] = 0;
                    }
                    $group[$lang][$genre] += $stat['c'];
                }
                if (empty($group[$lang]['all'])) {
                    $group[$lang]['all'] = 0;
                }
                $group[$lang]['all'] += $stat['c'];
            }
        }
        return $group;
    }

    private function translatedTitle(string $genre, string $lang)
    {
        if ($genre === 'all') {
            return 'All';
        }
        $default = ucfirst($genre);
        if (empty($this->keyToNum[$genre])) {
            $movieGenres = $this->genresRepo->getApi()->getMovieGenres(['language' => 'en']);
            $showGenres = $this->genresRepo->getApi()->getTvGenres(['language' => 'en']);
            foreach ($movieGenres['genres'] as $genreItem) {
                $this->keyToNum[strtolower($genreItem['name'])] = $genreItem['id'];
                $this->numToTranslated['en:' . $genreItem['id']] = $genreItem['name'];
            }
            foreach ($showGenres['genres'] as $genreItem) {
                $this->keyToNum[strtolower($genreItem['name'])] = $genreItem['id'];
                $this->numToTranslated['en:' . $genreItem['id']] = $genreItem['name'];
            }
        }

        if (empty($this->keyToNum[$genre])) {
            return $default;
        }

        $num = $this->keyToNum[$genre];
        if (empty($this->numToTranslated[$lang . ':' . $num])) {
            $movieGenres = $this->genresRepo->getApi()->getMovieGenres(['language' => $lang]);
            $showGenres = $this->genresRepo->getApi()->getTvGenres(['language' => $lang]);
            foreach ($movieGenres['genres'] as $genreItem) {
                $this->numToTranslated[$lang . ':' . $genreItem['id']] = $genreItem['name'];
            }
            foreach ($showGenres['genres'] as $genreItem) {
                $this->numToTranslated[$lang . ':' . $genreItem['id']] = $genreItem['name'];
            }
        }

        return $this->numToTranslated[$lang . ':' . $num]
            ?? $this->numToTranslated['en:' . $num]
            ?? $default;
    }
}
