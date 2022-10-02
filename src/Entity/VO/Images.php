<?php

namespace App\Entity\VO;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Images
{
    public const IMAGE_BASE = 'http://image.tmdb.org/t/p/w500';

    /**
     * @ORM\Column(type="string")
     */
    protected string $poster = '';
    /**
     * @ORM\Column(type="string")
     */
    protected string $fanart = '';
    /**
     * @ORM\Column(type="string")
     */
    protected string $banner = '';

    public function isEmpty()
    {
        return $this->banner === ''
            && $this->fanart === ''
            && $this->poster === '';
    }

    public function getApiArray(): array
    {
        return [
            'poster' => $this->getPoster(),
            'fanart' => $this->getFanart(),
            'banner' => $this->getBanner(),
        ];
    }

    public function getPoster()
    {
        return $this->get($this->poster);
    }

    public function setPoster($poster)
    {
        $this->poster = $poster;
        return $this;
    }

    protected function get($item)
    {
        $cleaned = str_replace(self::IMAGE_BASE, '', $item);
        return $cleaned ? (self::IMAGE_BASE . $cleaned) : '';
    }

    public function getFanart()
    {
        return $this->get($this->fanart);
    }

    public function setFanart($fanart)
    {
        $this->fanart = $fanart;
        return $this;
    }

    public function getBanner()
    {
        return $this->get($this->banner);
    }

    public function setBanner($banner)
    {
        $this->banner = $banner;
        return $this;
    }
}
