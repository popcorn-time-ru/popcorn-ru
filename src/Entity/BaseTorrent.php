<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TorrentRepository")
 * @ORM\Table(name="torrent")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"movie" = "MovieTorrent", "show" = "ShowTorrent"})
 */
abstract class BaseTorrent
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
     * @var File[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\File", mappedBy="torrent",
     *     cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $files;
    public function getFiles() { return $this->files; }
    public function setFiles(array $files):self {
        /** @var File[] $files */
        $existFiles = [];
        foreach ($files as $n => $file) {
            foreach ($this->files as $exist) {
                if ($exist->equals($file)) {
                    $existFiles[] = $exist;
                    unset($files[$n]);
                }
            }
        }
        foreach ($this->files as $file) {
            if (!in_array($file, $existFiles)) {
                $this->files->removeElement($file);
            }
        }
        foreach ($files as $file) {
            $file->setTorrent($this);
            $this->files->add($file);
        }

        return $this;
    }

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $providerExternalId;
    public function getProviderExternalId() { return $this->providerExternalId; }
    public function setProviderExternalId($providerExternalId) { $this->providerExternalId = $providerExternalId; return $this;}

    //<editor-fold desc="Movie Api Data">
    /**
     * @var string
     * @ORM\Column(type="string", length=3000)
     */
    protected $url;
    public function getUrl() { return $this->url; }
    public function setUrl($url) { $this->url = $url; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $language;
    public function getLanguage() { return $this->language; }
    public function setLanguage($language) { $this->language = $language; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $quality;
    public function getQuality() { return $this->quality; }
    public function setQuality($quality) { $this->quality = $quality; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $provider;
    public function getProvider() { return $this->provider; }
    public function setProvider($provider) { $this->provider = $provider; return $this;}

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $filesize;
    public function getFilesize() { return $this->filesize; }
    public function setFilesize($filesize) { $this->filesize = $filesize; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="bigint")
     */
    protected $size;
    public function getSize() { return $this->size; }
    public function setSize($size) { $this->size = $size; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $peer;
    public function getPeer() { return $this->peer; }
    public function setPeer($peer) { $this->peer = $peer; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $seed;
    public function getSeed() { return $this->seed; }
    public function setSeed($seed) { $this->seed = $seed; return $this;}
    //</editor-fold>
}
