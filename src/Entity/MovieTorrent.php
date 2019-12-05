<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TorrentRepository")
 */
class MovieTorrent extends BaseTorrent
{
    /**
     * @var Movie
     * @ORM\ManyToOne(targetEntity="App\Entity\Movie", inversedBy="torrents")
     */
    protected $movie;
    public function getMovie(): Movie { return $this->movie; }
    public function setMovie(Movie $movie): self { $this->movie = $movie; return $this; }
}
