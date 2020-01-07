<?php

namespace App\Entity\VO;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Images
{
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $poster = '';
    public function getPoster() { return $this->poster; }
    public function setPoster($poster) { $this->poster = $poster; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $fanart = '';
    public function getFanart() { return $this->fanart; }
    public function setFanart($fanart) { $this->fanart = $fanart; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $banner = '';
    public function getBanner() { return $this->banner; }
    public function setBanner($banner) { $this->banner = $banner; return $this;}

    public function getApiArray(): array
    {
        return [
            'poster' => $this->getPoster(),
            'fanart' => $this->getFanart(),
            'banner' => $this->getBanner(),
        ];
    }
}
