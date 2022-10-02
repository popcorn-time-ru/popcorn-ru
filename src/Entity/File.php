<?php

namespace App\Entity;

use App\Entity\Torrent\BaseTorrent;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 */
class File
{
    /**
     * @var BaseTorrent
     * @ORM\ManyToOne(targetEntity="App\Entity\Torrent\ShowTorrent", inversedBy="files")
     */
    protected $torrent;
    /**
     * @var \Doctrine\Common\Collections\Collection|Episode[]
     * @ORM\ManyToMany(targetEntity="Episode", inversedBy="files", cascade={"persist"})
     * @ORM\JoinTable(name="episodes_files",
     *      joinColumns={@ORM\JoinColumn(name="file_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="episode_id", referencedColumnName="id")}
     * )
     */
    protected $episodes;
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=500)
     */
    private $name;
    /**
     * @ORM\Column(type="bigint")
     */
    private $size;

    /**
     * File constructor.
     *
     * @param $name
     * @param $size
     */
    public function __construct(string $name, int $size)
    {
        $this->name = $name;
        $this->size = $size;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTorrent(): BaseTorrent
    {
        return $this->torrent;
    }

    public function setTorrent(BaseTorrent $torrent): self
    {
        $this->torrent = $torrent;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function equals(File $file): bool
    {
        return $this->size == $file->size
            && $this->name === $file->name;
    }

    public function linkEpisode(Episode $episode)
    {
        if ($this->episodes->contains($episode)) {
            return;
        }
        $this->episodes->add($episode);
    }

    public function unlinkEpisode(Episode $episode)
    {
        if (!$this->episodes->contains($episode)) {
            return;
        }
        $this->episodes->removeElement($episode);
    }

    public function isEpisode(Episode $episode)
    {
        return $this->episodes->contains($episode);
    }
}
