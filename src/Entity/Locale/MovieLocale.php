<?php

namespace App\Entity\Locale;

use App\Entity\Movie;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class MovieLocale extends BaseLocale
{
    /**
     * @var Movie
     * @ORM\ManyToOne(targetEntity="App\Entity\Movie", inversedBy="locales")
     * @ORM\JoinColumn(name="media_id")
     */
    protected $movie;

    public function getMovie(): Movie
    {
        return $this->movie;
    }

    public function setMovie(Movie $movie): self
    {
        $this->movie = $movie;
        return $this;
    }

    public function getMedia()
    {
        return $this->movie;
    }
}
