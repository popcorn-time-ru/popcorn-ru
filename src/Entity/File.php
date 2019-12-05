<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FileRepository")
 */
class File
{
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

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;
    public function getId(): ?int { return $this->id; }

    /**
     * @var BaseTorrent
     * @ORM\ManyToOne(targetEntity="App\Entity\BaseTorrent", inversedBy="files")
     */
    protected $torrent;
    public function getTorrent(): BaseTorrent { return $this->torrent; }
    public function setTorrent(BaseTorrent $torrent): self { $this->torrent = $torrent; return $this; }

    /**
     * @ORM\Column(type="string")
     */
    private $name;
    public function getName(): string { return $this->name; }
    public function setName(string $name): self {$this->name = $name; return $this;}

    /**
     * @ORM\Column(type="bigint")
     */
    private $size;
    public function getSize(): int { return $this->size; }
    public function setSize(int $size): self { $this->size = $size; return $this;}

    public function equals(File $file): bool
    {
        return $this->size == $file->size
            && $this->name === $file->name;
    }
}
