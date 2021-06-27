<?php

namespace App\Entity\Torrent;

use App\Entity\File;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

trait FilesTrait
{
    /**
     * @var File[]&ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\File", mappedBy="torrent",
     *     cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $files;
    public function getFiles() { return $this->files ?: new ArrayCollection(); }

    public function setFiles(array $files) {
        /** @var BaseTorrent $this */
        if (!$this->files) {
            $this->files = new ArrayCollection();
        }
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

        $size = 0;
        foreach ($this->files as $file) {
            $size+=$file->getSize();
        }
        $this->setSize($size);

        return $this;
    }
}
