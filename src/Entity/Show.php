<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="shows")
 * @ORM\Entity(repositoryClass="App\Repository\ShowRepository")
 */
class Show extends BaseMedia
{
    public function __construct()
    {
        parent::__construct();
        $this->torrents = new ArrayCollection();
    }

    /**
     * @var ShowTorrent[]
     * @ORM\OneToMany(targetEntity="App\Entity\ShowTorrent", fetch="EAGER", mappedBy="show")
     * @ORM\OrderBy({"peer" = "ASC"})
     */
    protected $torrents;
    public function getTorrents() { return $this->torrents; }

    //<editor-fold desc="Show Api Data">
    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
     */
    protected $imdb;
    public function getImdb() { return $this->imdb; }
    public function setImdb($imdb) { $this->imdb = $imdb; return $this;}
    //</editor-fold>
}
