<?php

namespace App\Entity\Locale;

use App\Entity\Episode\Episode;
use App\Entity\MySqlString;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Locale\EpisodeLocaleRepository")
 * @ORM\Table(name="episode_locale", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="episodeLocale", columns={"episode_id", "locale"})
 * })
 */
class EpisodeLocale
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
    }

    /**
     * @var Episode
     * @ORM\ManyToOne(targetEntity="App\Entity\Episode\Episode", inversedBy="locales")
     */
    protected $episode;
    public function getEpisode(): Episode { return $this->episode; }
    public function setEpisode(Episode $episode): self { $this->episode = $episode; return $this; }

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
    protected $overview = '';
    public function getOverview() { return $this->overview; }
    public function setOverview($overview) { $this->overview = $overview; return $this;}

    public function isEmpty() {
        return $this->title === ''
            && $this->overview === '';
    }
}
