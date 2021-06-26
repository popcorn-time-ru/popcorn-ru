<?php

namespace App\Entity;

use App\Entity\Locale\AnimeLocale;
use App\Entity\Torrent\AnimeTorrent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="anime")
 * @ORM\Entity(repositoryClass="App\Repository\AnimeRepository")
 */
class Anime extends BaseMedia
{
    public function __construct()
    {
        parent::__construct();
        $this->torrents = new ArrayCollection();
        $this->episodes = new ArrayCollection();
    }

    /**
     * @var AnimeTorrent[]&Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Torrent\AnimeTorrent", fetch="EAGER", mappedBy="anime")
     * @ORM\OrderBy({"peer" = "DESC"})
     */
    protected $torrents;
    public function getTorrents() { return $this->torrents; }

    /**
     * @var AnimeLocale[]
     * @ORM\OneToMany(targetEntity="App\Entity\Locale\AnimeLocale", fetch="EAGER", mappedBy="media")
     */
    protected $locales;
    public function getLocales() { return $this->locales; }

    /**
     * @var Episode[]&Collection
     * @ORM\OneToMany(targetEntity="App\Entity\Episode", mappedBy="show")
     */
    protected $episodes;
    public function getEpisodes() { return $this->episodes; }
    public function addEpisode(Episode $episode) {
        if (!$this->episodes->contains($episode)) {
            $this->episodes->add($episode);
        }
        return $this;
    }

    //<editor-fold desc="Show Api Data">
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
    protected $tvdb;
    public function getTvdb() { return $this->tvdb; }
    public function setTvdb($tvdb) { $this->tvdb = $tvdb; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $slug;
    public function getSlug() { return $this->slug; }
    public function setSlug($slug) { $this->slug = $slug; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $country;
    public function getCountry() { return $this->country; }
    public function setCountry($country) { $this->country = $country; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $network;
    public function getNetwork() { return $this->network; }
    public function setNetwork($network) { $this->network = $network; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $airDay;
    public function getAirDay() { return $this->airDay; }
    public function setAirDay($airDay) { $this->airDay = $airDay; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $airTime;
    public function getAirTime() { return $this->airTime; }
    public function setAirTime($airTime) { $this->airTime = $airTime; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $status;
    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $numSeasons;
    public function getNumSeasons() { return $this->numSeasons; }
    public function setNumSeasons($numSeasons) { $this->numSeasons = $numSeasons; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $lastUpdated;
    public function getLastUpdated() { return $this->lastUpdated; }
    public function setLastUpdated($lastUpdated) { $this->lastUpdated = $lastUpdated; return $this;}

    //</editor-fold>

    //<editor-fold desc="Anime Api Data">
    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    protected $kitsu;
    public function getKitsu() { return $this->kitsu; }
    public function setKitsu($kitsu) { $this->kitsu = $kitsu; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $mal;
    public function getMal() { return $this->mal; }
    public function setMal($mal) { $this->mal = $mal; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $type;
    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; return $this;}

    //</editor-fold>
}
