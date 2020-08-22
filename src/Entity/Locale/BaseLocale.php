<?php

namespace App\Entity\Locale;

use App\Entity\MySqlString;
use App\Entity\VO\Images;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Locale\BaseLocaleRepository")
 * @ORM\Table(name="locale")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"movie" = "MovieLocale", "show" = "ShowLocale"})
 */
abstract class BaseLocale
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     */
    protected $id;
    public function getId(): UuidInterface { return $this->id; }

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->images = new Images();
    }

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $locale;
    public function getLocale() { return $this->locale; }
    public function setLocale($locale) { $this->locale = $locale; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title = '';
    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; return $this;}

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $synopsis = '';
    public function getSynopsis() { return $this->synopsis; }
    public function setSynopsis($synopsis) { $this->synopsis = $synopsis; return $this;}

    /**
     * @var Images
     * @ORM\Embedded(class="App\Entity\VO\Images", columnPrefix="images_")
     */
    protected $images;
    public function getImages() { return $this->images; }
}
