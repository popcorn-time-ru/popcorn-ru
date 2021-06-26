<?php

namespace App\Entity;

use App\Entity\Locale\AnimeLocale;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Anime extends Show
{
    public function __construct()
    {
        parent::__construct();
        $this->torrents = new ArrayCollection();
        $this->episodes = new ArrayCollection();
    }

    /**
     * @var AnimeLocale[]
     * @ORM\OneToMany(targetEntity="App\Entity\Locale\AnimeLocale", fetch="EAGER", mappedBy="media")
     */
    protected $locales;
    public function getLocales() { return $this->locales; }

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
