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
    protected $percentage = 0;
    public function getPercentage() { return $this->percentage; }
    public function setPercentage($percentage) { $this->percentage = $percentage; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $watching = 0;
    public function getWatching() { return $this->watching; }
    public function setWatching($watching) { $this->watching = $watching; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $watchers = 0;
    public function getWatchers() { return $this->watchers; }
    public function setWatchers($watchers) { $this->watchers = $watchers; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $votes = 0;
    public function getVotes() { return $this->votes; }
    public function setVotes($votes) { $this->votes = $votes; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $loved = 0;
    public function getLoved() { return $this->loved; }
    public function setLoved($loved) { $this->loved = $loved; return $this;}

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    protected $hated = 0;
    public function getHated() { return $this->hated; }
    public function setHated($hated) { $this->hated = $hated; return $this;}

    public function getApiArray(): array
    {
        return [
            'percentage' => $this->getPercentage(),
            'watching' => $this->getWatching(),
            'votes' => $this->getVotes(),
            'loved' => $this->getLoved(),
            'hated' => $this->getHated(),
        ];
    }
}
