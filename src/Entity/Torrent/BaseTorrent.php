<?php

namespace App\Entity\Torrent;

use App\Entity\BaseMedia;
use App\Entity\File;
use App\Entity\MySqlString;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TorrentRepository")
 * @ORM\Table(name="torrent", indexes={
 *     @ORM\Index(name="providerId", columns={"provider","provider_external_id"})
 * })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
 * @ORM\DiscriminatorMap({"movie" = "MovieTorrent", "show" = "ShowTorrent", "episode"="EpisodeTorrent"})
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
    /**
     * @var string
     * @ORM\Column(type="uuid")
     */
    protected $mediaId;
    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $lastCheckAt;
    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $syncAt;
    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $active = true;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $providerExternalId;
    /**
     * @var string
     * @ORM\Column(type="string", length=1024)
     */
    protected $providerTitle;
    /**
     * @var string
     * @ORM\Column(type="string", length=3000)
     */
    protected $url;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $language;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $quality;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $provider;
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $filesize;
    /**
     * @var integer
     * @ORM\Column(type="bigint")
     */
    protected $size;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $peer;
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $seed;

    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function check()
    {
        $this->lastCheckAt = new DateTime();
        return $this;
    }

    public function isChecked(DateTime $date = null)
    {
        $interval = $this->lastCheckAt->diff($date ?: new DateTime());
        return $interval->days < 1;
    }

    public function sync()
    {
        $this->lastCheckAt = new DateTime();
        $this->syncAt = new DateTime();
        return $this;
    }

    //<editor-fold desc="Movie Api Data">

    public function isSynced(DateTime $date = null)
    {
        $interval = $this->syncAt->diff($date ?: new DateTime());
        return $interval->days < 1;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    public function setFiles(array $files)
    {
        /** @var File[] $files */
        $size = 0;
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        $this->setSize($size);

        return $this;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function setSize($size)
    {
        if ($size == 0) {
            $size = 1024 ** 3;
        }
        $this->size = $size;
        $this->filesize = $this->formatBytes($size);
    }

    public function getProviderExternalId()
    {
        return $this->providerExternalId;
    }

    public function setProviderExternalId($providerExternalId)
    {
        $this->providerExternalId = $providerExternalId;
        return $this;
    }

    public function getProviderTitle()
    {
        return $this->providerTitle;
    }

    public function setProviderTitle($providerTitle)
    {
        $this->providerTitle = trim($providerTitle);
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    public function getQuality()
    {
        return $this->quality;
    }

    public function setQuality($quality)
    {
        $this->quality = $quality;
        return $this;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    public function getFilesize()
    {
        return $this->filesize;
    }

    public function getPeer()
    {
        return $this->peer;
    }

    public function setPeer($peer)
    {
        $this->peer = $peer;
        return $this;
    }

    public function getSeed()
    {
        return $this->seed;
    }

    public function setSeed($seed)
    {
        $this->seed = $seed;
        return $this;
    }

    //</editor-fold>

    abstract public function getMedia(): BaseMedia;

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
