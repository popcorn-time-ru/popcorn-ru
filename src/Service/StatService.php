<?php

namespace App\Service;

use App\Repository\MediaStatRepository;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use Tmdb\Repository\GenreRepository;

class StatService
{
    /** @var MovieRepository */
    protected $movieRepo;

    /** @var ShowRepository */
    protected $showRepo;

    /** @var MediaStatRepository */
    protected $statRepo;

    /** @var GenreRepository */
    protected $genresRepo;

    public function __construct(MovieRepository $movieRepo, ShowRepository $showRepo, MediaStatRepository $statRepo, GenreRepository $genresRepo)
    {
        $this->movieRepo = $movieRepo;
        $this->showRepo = $showRepo;
        $this->genresRepo = $genresRepo;
        $this->statRepo = $statRepo;
    }

    public function calculateMediaStat()
    {
        $movieStat = $this->groupGenreStat($this->movieRepo->getGenreStatistics());
        $showStat = $this->groupGenreStat($this->showRepo->getGenreStatistics());

        foreach ($movieStat as $lang => $langStat) {
            foreach ($langStat as $genre => $count) {
                if ($genre === 'unknown') {
                    continue;
                }
                $stat = $this->statRepo->getOrCreate('movie', $genre, $lang);
                if (empty($stat->getTitle())) {
                    $stat->setTitle($this->translatedTitle($genre, $lang));
                }
                $stat->setCount($count);
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
                $stat->setCount($count);
            }
        }
        $this->statRepo->flush();
    }

    private function groupGenreStat(array $allRows): array
    {
        $group = [];
        foreach ($allRows as $stat) {
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

    private $keyToNum = [];
    private $numToTranslated = [];

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
