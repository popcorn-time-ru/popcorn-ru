<?php

namespace App\Entity\Episode;

use App\Entity\File;
use App\Entity\Locale\EpisodeLocale;
use App\Entity\Locale\BaseLocale;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\EpisodeTorrent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
 * @ORM\DiscriminatorMap({"show" = "ShowEpisode", "anime"="AnimeEpisode"})
 */
abstract class Episode
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
        $this->files = new ArrayCollection();
    }

    /**
     * @var EpisodeTorrent[]
     * @ORM\OneToMany(targetEntity="App\Entity\Torrent\EpisodeTorrent", fetch="LAZY", mappedBy="episode")
     * @ORM\OrderBy({"peer" = "DESC"})
     */
    protected $torrents;
    public function getTorrents() { return $this->torrents; }

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

    /**
     * @var EpisodeLocale[]
     * @ORM\OneToMany(targetEntity="App\Entity\Locale\EpisodeLocale", fetch="LAZY", mappedBy="episode")
     */
    protected $locales;
    public function getLocales() { return $this->locales; }

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $season;
    public function getSeason() { return $this->season; }
    public function setSeason($season) { $this->season = $season; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $episode;
    public function getEpisode() { return $this->episode; }
    public function setEpisode($episode) { $this->episode = $episode; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $firstAired;
    public function getFirstAired() { return $this->firstAired; }
    public function setFirstAired($firstAired) { $this->firstAired = $firstAired; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; return $this;}

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $overview;
    public function getOverview() { return $this->overview; }
    public function setOverview($overview) { $this->overview = $overview; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $tvdb = 0;
    public function getTvdb() { return $this->tvdb; }
    public function setTvdb($tvdb) { $this->tvdb = $tvdb; return $this;}

    /**
     * @var \Doctrine\Common\Collections\Collection|File[]
     * @ORM\ManyToMany(targetEntity="App\Entity\File", mappedBy="episodes", cascade={"persist"})
     * @ORM\JoinTable(name="episodes_files",
     *      joinColumns={@ORM\JoinColumn(name="episode_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")}
     * )
     */
    protected $files;
    public function getFiles() { return $this->files; }
    public function addFile(File $file) {
        if ($this->files->contains($file)) {
            return;
        }
        $this->files->add($file);
        $file->linkEpisode($this);
    }
    public function removeFile(File $file) {
        if (!$this->files->contains($file)) {
            return;
        }
        $this->files->removeElement($file);
        $file->unlinkEpisode($this);
    }
}
