<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CustomIdsRepository")
 */
class CustomIds
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string", unique=true)
     */
    protected string $imdb;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $tmdb = 0;
    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $tvdb = 0;
    /**
     * @ORM\Column(type="integer")
     */
    protected $kinopoisk = 0;

    public function getImdb()
    {
        return $this->imdb;
    }

    public function setImdb($imdb)
    {
        $this->imdb = $imdb;
        return $this;
    }

    public function getTmdb()
    {
        return $this->tmdb;
    }

    public function setTmdb($tmdb)
    {
        $this->tmdb = $tmdb;
        return $this;
    }

    public function getTvdb()
    {
        return $this->tvdb;
    }

    public function setTvdb($tvdb)
    {
        $this->tvdb = $tvdb;
        return $this;
    }

    public function getKinopoisk()
    {
        return $this->kinopoisk;
    }

    public function setKinopoisk($kinopoisk)
    {
        $this->kinopoisk = $kinopoisk;
        return $this;
    }
}
