<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MediaStatRepository")
 */
class MediaStat
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;
    /**
     * @var string
     * @ORM\Column(type="string", length=10)
     */
    protected $type;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $genre;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $language;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $countLang;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $countAll;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getGenre()
    {
        return $this->genre;
    }

    public function setGenre($genre)
    {
        $this->genre = $genre;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getCountLang()
    {
        return $this->countLang;
    }

    public function setCountLang($countLang)
    {
        $this->countLang = $countLang;
        return $this;
    }

    public function getCountAll()
    {
        return $this->countAll;
    }

    public function setCountAll($countAll)
    {
        $this->countAll = $countAll;
        return $this;
    }

}
