<?php

namespace App\Entity\Episode;

use App\Entity\Show;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeRepository")
 */
class ShowEpisode extends Episode
{
    /**
     * @var Show
     * @ORM\ManyToOne(targetEntity="App\Entity\Show", inversedBy="episodes")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $show;
    public function getShow(): Show { return $this->show; }
    public function setShow(Show $show): self { $this->show = $show; return $this; }
}
