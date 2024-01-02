<?php

namespace App\Entity\Locale;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Show;

#[ORM\Entity]
class ShowLocale extends BaseLocale
{
    /**
     * @var Show
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Show', inversedBy: 'locales')]
    #[ORM\JoinColumn(name: 'media_id')]
    protected $show;
    public function getShow(): Show { return $this->show; }
    public function setShow(Show $show): self { $this->show = $show; return $this; }

    public function getMedia() { return $this->show; }
}
