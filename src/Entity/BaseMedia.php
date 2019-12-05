<?php

namespace App\Entity;

use App\Entity\VO\Images;
use App\Entity\VO\Rating;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\MappedSuperclass()
 */
class BaseMedia
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
        $this->rating = new Rating();
    }

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $syncAt;
    public function synced(int $delta):bool
    {
        return $this->syncAt &&
            $this->syncAt->getTimestamp() + $delta > (new \DateTime())->getTimestamp();
    }
    public function sync() { $this->syncAt = new \DateTime(); return $this;}

    //<editor-fold desc="Api Data">
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $year;
    public function getYear() { return $this->year; }
    public function setYear($year) { $this->year = $year; return $this;}

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $synopsis;
    public function getSynopsis() { return $this->synopsis; }
    public function setSynopsis($synopsis) { $this->synopsis = $synopsis; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $runtime;
    public function getRuntime() { return $this->runtime; }
    public function setRuntime($runtime) { $this->runtime = $runtime; return $this;}

    /**
     * @var array
     * @ORM\Column(type="simple_array")
     */
    protected $genres;
    public function getGenres() { return $this->genres; }
    public function setGenres($genres) { $this->genres = $genres; return $this;}

    /**
     * @var Images
     * @ORM\Embedded(class="App\Entity\VO\Images", columnPrefix="images_")
     */
    protected $images;
    public function getImages() { return $this->images; }

    /**
     * @var Rating
     * @ORM\Embedded(class="App\Entity\VO\Rating", columnPrefix="rating_")
     */
    protected $rating;
    public function getRating() { return $this->rating; }
    //</editor-fold>
}
