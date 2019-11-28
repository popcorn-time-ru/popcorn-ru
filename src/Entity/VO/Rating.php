<?php

namespace App\Entity\VO;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Rating
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $percentage;
    public function getPercentage() { return $this->percentage; }
    public function setPercentage($percentage) { $this->percentage = $percentage; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $watching;
    public function getWatching() { return $this->watching; }
    public function setWatching($watching) { $this->watching = $watching; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $votes;
    public function getVotes() { return $this->votes; }
    public function setVotes($votes) { $this->votes = $votes; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $loved;
    public function getLoved() { return $this->loved; }
    public function setLoved($loved) { $this->loved = $loved; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $hated;
    public function getHated() { return $this->hated; }
    public function setHated($hated) { $this->hated = $hated; return $this;}
}
