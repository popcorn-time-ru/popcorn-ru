<?php

namespace App\Entity\Torrent;

use App\Entity\Anime;
use App\Entity\BaseMedia;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TorrentRepository")
 */
class AnimeTorrent extends BaseTorrent
{
    use FilesTrait;

    /**
     * @var Anime
     * @ORM\ManyToOne(targetEntity="App\Entity\Anime", inversedBy="torrents")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $anime;
    public function getAnime(): Anime { return $this->anime; }
    public function setAnime(Anime $anime): self { $this->anime = $anime; return $this; }

    public function getMedia(): BaseMedia { return $this->anime;}
}
