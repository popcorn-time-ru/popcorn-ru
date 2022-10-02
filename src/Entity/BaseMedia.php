<?php

namespace App\Entity;

use App\Entity\Torrent\BaseTorrent;
use App\Entity\VO\Images;
use App\Entity\VO\Rating;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\MappedSuperclass()
 */
abstract class BaseMedia
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     */
    protected UuidInterface $id;
    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $createdAt;
    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $syncAt;
    /**
     * @var string[]
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected $existTranslations;
    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $lastActiveCheck;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $year;
    /**
     * @var string
     * @ORM\Column(type="string", length=2)
     */
    protected $origLang;
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $synopsis;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $runtime;
    /**
     * @var array
     * @ORM\Column(type="simple_array")
     */
    protected $genres;
    /**
     * @var Images
     * @ORM\Embedded(class="App\Entity\VO\Images", columnPrefix="images_")
     */
    protected $images;
    /**
     * @var Rating
     * @ORM\Embedded(class="App\Entity\VO\Rating", columnPrefix="rating_")
     */
    protected $rating;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->createdAt =
        $this->syncAt =
        $this->lastActiveCheck =
            new DateTime();
        $this->images = new Images();
        $this->rating = new Rating();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedForElastic(): ?DateTime
    {
        return $this->createdAt->getTimestamp() > 0 ? $this->createdAt : null;
    }

    public function synced(int $delta): bool
    {
        return $this->syncAt &&
            $this->syncAt->getTimestamp() + $delta > (new \DateTime())->getTimestamp();
    }

    public function sync()
    {
        $this->syncAt = new \DateTime();
        return $this;
    }

    public function getSynAt()
    {
        return $this->syncAt;
    }

    //<editor-fold desc="Api Data">

    public function getExistTranslations(): array
    {
        return $this->existTranslations ?? [];
    }

    public function addExistTranslation(string $translations): self
    {
        if (!is_array($this->existTranslations)) {
            $this->existTranslations = [];
        }
        if (!in_array($translations, $this->existTranslations, true)) {
            $this->existTranslations[] = $translations;
        }
        $this->existTranslations = array_filter($this->existTranslations);
        sort($this->existTranslations);
        return $this;
    }

    public function syncTranslations(): self
    {
        $translations = [];
        foreach ($this->getTorrents() as $tor) {
            if ($tor->getActive()) {
                $translations[$tor->getLanguage()] = 1;
            }
        }
        $this->existTranslations = array_keys($translations);
        sort($this->existTranslations);
        return $this;
    }

    /**
     * @return BaseTorrent[]
     */
    abstract public function getTorrents();

    public function getLastActiveCheck()
    {
        return $this->lastActiveCheck;
    }

    public function setLastActiveCheck($lastActiveCheck)
    {
        $this->lastActiveCheck = $lastActiveCheck;
        return $this;
    }

    abstract public function getLocales();

    /**
     * @param string $locale
     * @return BaseTorrent[]&\Generator
     */
    public function getLocaleTorrents(string $locale)
    {
        foreach ($this->getTorrents() as $torrent) {
            if ($torrent->getLanguage() == $locale) {
                yield $torrent;
            }
        }
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

    public function getYear()
    {
        return $this->year;
    }

    public function setYear($year)
    {
        $this->year = $year;
        return $this;
    }

    public function getOrigLang()
    {
        return $this->origLang;
    }

    public function setOrigLang($origLang)
    {
        $this->origLang = $origLang;
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

    public function getRuntime()
    {
        return $this->runtime;
    }

    public function setRuntime($runtime)
    {
        $this->runtime = $runtime;
        return $this;
    }

    public function getGenres()
    {
        return $this->genres;
    }

    public function setGenres($genres)
    {
        $this->genres = $genres;
        sort($this->genres);
        return $this;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function getRating()
    {
        return $this->rating;
    }
    //</editor-fold>
}
