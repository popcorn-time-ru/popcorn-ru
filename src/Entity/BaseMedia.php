<?php

namespace App\Entity;

use App\Entity\Locale\BaseLocale;
use App\Entity\Locale\EpisodeLocale;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\VO\Images;
use App\Entity\VO\Rating;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Generator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\MappedSuperclass()
 */
abstract class BaseMedia extends \App\Entity\Show
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     */
    protected UuidInterface $id;
    public function getId(): UuidInterface { return $this->id; }

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

    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $createdAt;
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getCreatedForElastic(): ?DateTime {
        return $this->createdAt->getTimestamp() > 0 ? $this->createdAt : null;
    }

    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $syncAt;
    public function synced(int $delta): bool
    {
        return $this->syncAt->getTimestamp() + $delta > (new \DateTime())->getTimestamp();
    }
    public function sync() { $this->syncAt = new \DateTime(); return $this;}
    public function getSynAt() { return $this->syncAt;}

    /**
     * @var string[]
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected $existTranslations;
    public function getExistTranslations(): array { return $this->existTranslations ?? []; }
    public function addExistTranslation(string $translations): self {
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
    public function syncTranslations(): self {
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
     * @ORM\Column(type="datetime")
     */
    protected DateTime $lastActiveCheck;
    public function getLastActiveCheck(): DateTime
    { return $this->lastActiveCheck; }
    public function setLastActiveCheck($lastActiveCheck): BaseMedia|static
    { $this->lastActiveCheck = $lastActiveCheck; return $this;}

    /**
     * @return BaseTorrent[]
     */
    public function getTorrents()
    {
        // TODO: Implement getTorrents() method.
    }
    public function getLocales()
    {
        // TODO: Implement getLocales() method.
    }

    /**
     * @param string $locale
     * @return array|Generator
     */
    public function getLocaleTorrents(string $locale): array|Generator
    {
        foreach ($this->getTorrents() as $torrent) {
            if ($torrent->getLanguage() == $locale) {
                yield $torrent;
            }
        }
    }

    //<editor-fold desc="Api Data">
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    public function getTitle(): string
    { return $this->title; }
    public function setTitle($title): BaseMedia|static
    { $this->title = $title; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $year;
    public function getYear(): string
    { return $this->year; }
    public function setYear($year): BaseMedia|static
    { $this->year = $year; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string", length=2)
     */
    protected $origLang;
    public function getOrigLang(): string
    { return $this->origLang; }
    public function setOrigLang($origLang): BaseMedia|static
    { $this->origLang = $origLang; return $this;}

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $synopsis;
    public function getSynopsis(): string
    { return $this->synopsis; }
    public function setSynopsis($synopsis): BaseMedia|static
    { $this->synopsis = $synopsis; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $runtime;
    public function getRuntime(): string
    { return $this->runtime; }
    public function setRuntime($runtime): BaseMedia|static
    { $this->runtime = $runtime; return $this;}

    /**
     * @var array
     * @ORM\Column(type="simple_array")
     */
    protected $genres;
    public function getGenres(): array
    { return $this->genres; }
    public function setGenres($genres): BaseMedia|static
    { $this->genres = $genres; sort($this->genres); return $this;}

    /**
     * @var Images
     * @ORM\Embedded(class="App\Entity\VO\Images", columnPrefix="images_")
     */
    protected $images;
    public function getImages(): Images
    { return $this->images; }

    /**
     * @var Rating
     * @ORM\Embedded(class="App\Entity\VO\Rating", columnPrefix="rating_")
     */
    protected $rating;
    public function getRating(): Rating
    { return $this->rating; }
    //</editor-fold>
}
