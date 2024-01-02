<?php

namespace App\Entity\VO;

use App\Service\MediaService;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Rating
{
    /**
     * @var integer
     */
    #[ORM\Column(type: 'integer')]
    protected $percentage = 0;
    public function getPercentage() { return $this->percentage; }
    public function setPercentage($percentage) { $this->percentage = $percentage; return $this;}

    /**
     * @var integer
     */
    #[ORM\Column(type: 'integer')]
    protected $watching = 0;
    public function getWatching() { return $this->watching; }
    public function setWatching($watching) { $this->watching = $watching; return $this;}

    /**
     * @var integer
     */
    #[ORM\Column(type: 'integer')]
    protected $watchers = 0;
    public function getWatchers() { return $this->watchers; }
    public function setWatchers($watchers) { $this->watchers = $watchers; return $this;}

    /**
     * @var integer
     */
    #[ORM\Column(type: 'integer')]
    protected $votes = 0;
    public function getVotes() { return $this->votes; }
    public function setVotes($votes) { $this->votes = $votes; return $this;}

    /**
     * @var float
     */
    #[ORM\Column(type: 'float')]
    protected $popularity = 0;
    public function getPopularity() { return $this->popularity; }
    public function setPopularity($popularity) { $this->popularity = $popularity; return $this;}

    /**
     * @var float
     */
    #[ORM\Column(type: 'float')]
    protected $weightRating = MediaService::IMDB_RATING;
    public function getWeightRating() { return $this->weightRating; }
    public function setWeightRating($weightRating) { $this->weightRating = $weightRating; return $this;}

    public function getApiArray(): array
    {
        return [
            'percentage' => $this->getPercentage(),
            'watching' => $this->getWatching(),
            'votes' => $this->getVotes(),
            // TODO: remove after fix in android app
            'loved' => 0,
            'hated' => 0,
        ];
    }
}
