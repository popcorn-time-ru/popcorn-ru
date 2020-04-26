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
    public function getId(): ?int { return $this->id; }

    /**
     * @var string
     * @ORM\Column(type="string", length=10)
     */
    protected $type;
    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $genre;
    public function getGenre() { return $this->genre; }
    public function setGenre($genre) { $this->genre = $genre; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $language;
    public function getLanguage() { return $this->language; }
    public function setLanguage($language) { $this->language = $language; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer", name="`count`")
     */
    protected $count;
    public function getCount() { return $this->count; }
    public function setCount($count) { $this->count = $count; return $this;}

}
