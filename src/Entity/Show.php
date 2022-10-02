<?php

namespace App\Entity;

use App\Entity\Locale\ShowLocale;
use App\Entity\Torrent\ShowTorrent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="shows")
 * @ORM\Entity(repositoryClass="App\Repository\ShowRepository")
 */
class Show extends BaseMedia
{
    /**
     * @var ShowTorrent[]&Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Torrent\ShowTorrent", fetch="LAZY", mappedBy="show")
     * @ORM\OrderBy({"peer" = "DESC"})
     */
    protected $torrents;
    /**
     * @var ShowLocale[]
     * @ORM\OneToMany(targetEntity="App\Entity\Locale\ShowLocale", fetch="LAZY", mappedBy="show")
     */
    protected $locales;
    /**
     * @var Episode[]&Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Episode", mappedBy="show")
     */
    protected $episodes;
    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    protected $imdb;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $tmdb;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $tvdb;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $slug;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $country;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $network;

    //<editor-fold desc="Show Api Data">
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $airDay;
    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $airTime;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $status;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $numSeasons;
    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastUpdated;

    public function __construct()
    {
        parent::__construct();
        $this->torrents = new ArrayCollection();
        $this->episodes = new ArrayCollection();
    }

    public function syncTranslations(): self
    {
        $translations = [];
        foreach ($this->getTorrents() as $tor) {
            if ($tor->getActive()) {
                $translations[$tor->getLanguage()] = 1;
            }
        }
        foreach ($this->getEpisodes() as $episode) {
            foreach ($episode->getTorrents() as $tor) {
                if ($tor->getActive()) {
                    $translations[$tor->getLanguage()] = 1;
                }
            }
        }
        $this->existTranslations = array_keys($translations);
        sort($this->existTranslations);
        return $this;
    }

    public function getTorrents()
    {
        return $this->torrents;
    }

    public function getEpisodes()
    {
        return $this->episodes;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function addEpisode(Episode $episode)
    {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
        }
        return $this;
    }

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

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    public function getNetwork()
    {
        return $this->network;
    }

    public function setNetwork($network)
    {
        $this->network = $network;
        return $this;
    }

    public function getAirDay()
    {
        return $this->airDay;
    }

    public function setAirDay($airDay)
    {
        $this->airDay = $airDay;
        return $this;
    }

    public function getAirTime()
    {
        return $this->airTime;
    }

    public function setAirTime($airTime)
    {
        $this->airTime = $airTime;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getNumSeasons()
    {
        return $this->numSeasons;
    }

    public function setNumSeasons($numSeasons)
    {
        $this->numSeasons = $numSeasons;
        return $this;
    }

    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    //</editor-fold>
}
