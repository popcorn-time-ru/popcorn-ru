<?php

namespace App\Entity\Locale;

use App\Entity\BaseMedia;
use App\Entity\MySqlString;
use App\Entity\VO\Images;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Locale\BaseLocaleRepository")
 * @ORM\Table(name="locale", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="mediaLocale", columns={"media_id", "locale"})
 * })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
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
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $locale;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title = '';
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $synopsis = '';
    /**
     * @var Images
     * @ORM\Embedded(class="App\Entity\VO\Images", columnPrefix="images_")
     */
    protected $images;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->images = new Images();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
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

    public function getSynopsis()
    {
        return $this->synopsis;
    }

    public function setSynopsis($synopsis)
    {
        $this->synopsis = $synopsis;
        return $this;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function isEmpty()
    {
        return $this->title === ''
            && $this->synopsis === ''
            && $this->images->isEmpty();
    }

    /**
     * @return BaseMedia
     */
    abstract public function getMedia();
}
