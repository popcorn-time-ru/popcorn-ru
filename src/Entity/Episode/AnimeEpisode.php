<?php

namespace App\Entity\Episode;

use App\Entity\Anime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeRepository")
 */
class AnimeEpisode extends Episode
{
    /**
     * @var Anime
     * @ORM\ManyToOne(targetEntity="App\Entity\Anime", inversedBy="episodes")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $anime;
    public function getAnime(): Anime { return $this->anime; }
    public function setAnime(Anime $anime): self { $this->anime = $anime; return $this; }
}
