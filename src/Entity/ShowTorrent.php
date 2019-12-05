<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ShowTorrent extends BaseTorrent
{
    /**
     * @var Show
     * @ORM\ManyToOne(targetEntity="App\Entity\Show", inversedBy="torrents")
     */
    protected $show;
    public function getShow(): Show { return $this->show; }
    public function setShow(Show $show): self { $this->show = $show; return $this; }
}
