<?php

namespace App\Entity\Torrent;

use App\Entity\BaseMedia;
use App\Entity\Show;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TorrentRepository")
 */
class ShowTorrent extends BaseTorrent
{
    use FilesTrait;

    /**
     * @var Show
     * @ORM\ManyToOne(targetEntity="App\Entity\Show", inversedBy="torrents")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $show;
    public function getShow(): Show { return $this->show; }
    public function setShow(Show $show): self { $this->show = $show; return $this; }

    public function getMedia(): BaseMedia { return $this->show;}
}
