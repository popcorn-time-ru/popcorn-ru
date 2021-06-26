<?php

namespace App\Entity\Locale;

use App\Entity\BaseMedia;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Show;

/**
 * @ORM\Entity()
 */
class ShowLocale extends BaseLocale
{
    /**
     * @var Show
     * @ORM\ManyToOne(targetEntity="App\Entity\Show", inversedBy="locales")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $media;
    public function getMedia(): BaseMedia { return $this->media; }
    public function setMedia(BaseMedia $media): self { $this->media = $media; return $this; }
}
