<?php

namespace App\Entity\Torrent;

use App\Entity\BaseMedia;
use App\Entity\Episode;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TorrentRepository")
 */
class EpisodeTorrent extends BaseTorrent
{
    /**
     * @var Episode
     * @ORM\ManyToOne(targetEntity="App\Entity\Episode", inversedBy="torrents")
     */
    protected $episode;
    public function getEpisode(): Episode { return $this->episode; }
    public function setEpisode(Episode $episode): self { $this->episode = $episode; return $this; }

    public function getMedia(): BaseMedia { return $this->episode->getShow();}
}
