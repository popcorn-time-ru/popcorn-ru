<?php

namespace App\Entity\Locale;

use App\Entity\BaseMedia;
use App\Entity\Anime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AnimeLocale extends BaseLocale
{
    /**
     * @var Anime
     * @ORM\ManyToOne(targetEntity="App\Entity\Anime", inversedBy="locales")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $media;
    public function getMedia(): BaseMedia { return $this->media; }
    public function setMedia(BaseMedia $media): self { $this->media = $media; return $this; }
}
