<?php

namespace App\Entity;

use App\Entity\VO\Images;
use App\Entity\VO\Rating;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MovieRepository")
 */
class Movie
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;
    public function getId(): ?int { return $this->id; }

    public function __construct()
    {
        $this->torrents = new ArrayCollection();
        $this->images = new Images();
        $this->rating = new Rating();
    }

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $syncAt;
    public function synced(int $delta):bool
    {
        return $this->syncAt &&
            $this->syncAt->getTimestamp() + $delta > (new \DateTime())->getTimestamp();
    }
    public function sync() { $this->syncAt = new \DateTime(); return $this;}


    /**
     * @var Torrent[]
     * @ORM\OneToMany(targetEntity="App\Entity\Torrent", fetch="EAGER", mappedBy="movie")
     * @ORM\OrderBy({"peer" = "ASC"})
     */
    protected $torrents;
    public function getTorrents() { return $this->torrents; }

    //<editor-fold desc="Movie Api Data">
    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    protected $imdb;
    public function getImdb() { return $this->imdb; }
    public function setImdb($imdb) { $this->imdb = $imdb; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $year;
    public function getYear() { return $this->year; }
    public function setYear($year) { $this->year = $year; return $this;}

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $synopsis;
    public function getSynopsis() { return $this->synopsis; }
    public function setSynopsis($synopsis) { $this->synopsis = $synopsis; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $runtime;
    public function getRuntime() { return $this->runtime; }
    public function setRuntime($runtime) { $this->runtime = $runtime; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $released;
    public function getReleased() { return $this->released; }
    public function setReleased($released) { $this->released = $released; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $trailer;
    public function getTrailer() { return $this->trailer; }
    public function setTrailer($trailer) { $this->trailer = $trailer; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $certification;
    public function getCertification() { return $this->certification; }
    public function setCertification($certification) { $this->certification = $certification; return $this;}

    /**
     * @var array
     * @ORM\Column(type="simple_array")
     */
    protected $genres;
    public function getGenres() { return $this->genres; }
    public function setGenres($genres) { $this->genres = $genres; return $this;}

    /**
     * @var Images
     * @ORM\Embedded(class="App\Entity\VO\Images", columnPrefix="images_")
     */
    protected $images;
    public function getImages() { return $this->images; }

    /**
     * @var Rating
     * @ORM\Embedded(class="App\Entity\VO\Rating", columnPrefix="rating_")
     */
    protected $rating;
    public function getRating() { return $this->rating; }
    //</editor-fold>
}
