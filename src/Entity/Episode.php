<?php

namespace App\Entity;

use App\Entity\Locale\EpisodeLocale;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\EpisodeTorrent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EpisodeRepository")
 */
class Episode
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     */
    protected $id;
    /**
     * @var EpisodeTorrent[]
     * @ORM\OneToMany(targetEntity="App\Entity\Torrent\EpisodeTorrent", fetch="LAZY", mappedBy="episode")
     * @ORM\OrderBy({"peer" = "DESC"})
     */
    protected $torrents;
    /**
     * @var EpisodeLocale[]
     * @ORM\OneToMany(targetEntity="App\Entity\Locale\EpisodeLocale", fetch="LAZY", mappedBy="episode")
     */
    protected $locales;
    /**
     * @var Show
     * @ORM\ManyToOne(targetEntity="App\Entity\Show", inversedBy="episodes")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $show;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $season;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $episode;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $firstAired;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $title;
    /**
     * @var string
     * @ORM\Column(type="text")
     */
    protected $overview;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $tvdb = 0;
    /**
     * @var \Doctrine\Common\Collections\Collection|File[]
     * @ORM\ManyToMany(targetEntity="File", mappedBy="episodes", cascade={"persist"})
     * @ORM\JoinTable(name="episodes_files",
     *      joinColumns={@ORM\JoinColumn(name="episode_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")}
     * )
     */
    protected $files;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
        $this->files = new ArrayCollection();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

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

    public function getTorrents()
    {
        return $this->torrents;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function getShow(): Show
    {
        return $this->show;
    }

    public function setShow(Show $show): self
    {
        $this->show = $show;
        return $this;
    }

    public function getSeason()
    {
        return $this->season;
    }

    public function setSeason($season)
    {
        $this->season = $season;
        return $this;
    }

    public function getEpisode()
    {
        return $this->episode;
    }

    public function setEpisode($episode)
    {
        $this->episode = $episode;
        return $this;
    }

    public function getFirstAired()
    {
        return $this->firstAired;
    }

    public function setFirstAired($firstAired)
    {
        $this->firstAired = $firstAired;
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

    public function getOverview()
    {
        return $this->overview;
    }

    public function setOverview($overview)
    {
        $this->overview = $overview;
        return $this;
    }

    public function getTvdb()
    {
        return $this->tvdb;
    }

    public function setTvdb($tvdb)
    {
        $this->tvdb = $tvdb;
        return $this;
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function addFile(File $file)
    {
        if ($this->files->contains($file)) {
            return;
        }
        $this->files->add($file);
        $file->linkEpisode($this);
    }

    public function removeFile(File $file)
    {
        if (!$this->files->contains($file)) {
            return;
        }
        $this->files->removeElement($file);
        $file->unlinkEpisode($this);
    }
}
