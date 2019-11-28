<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use App\Entity\Torrent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class TestTorrents extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $this->load1($manager);
        $this->load2($manager);
        $this->load3($manager);
    }

    public function load1(ObjectManager $manager)
    {
        $movie = new Movie();

        $movie
            ->setImdb('tt0076759')
            ->setTitle('Star Wars')
            ->setSynopsis('Princess Leia is captured and held hostage by the evil Imperial forces in their effort to take over the galactic Empire. Venturesome Luke Skywalker and dashing captain Han Solo team together with the loveable robot duo R2-D2 and C-3PO to rescue the beautiful princess and restore peace and justice in the Empire.')
            ->setReleased(233366400)
            ->setCertification('PG')
            ->setYear('1977')
            ->setGenres(["action", "adventure", "science-fiction"])
            ->setRuntime('121')
            ->setTrailer('http://youtube.com/watch?v=vZ734NWnAHA')
        ;

        $movie->getImages()
            ->setBanner('http://image.tmdb.org/t/p/w500/tvSlBzAdRE29bZe5yYWrJ2ds137.jpg')
            ->setFanart('http://image.tmdb.org/t/p/w500/4iJfYYoQzZcONB9hNzg0J0wWyPH.jpg')
            ->setPoster('http://image.tmdb.org/t/p/w500/tvSlBzAdRE29bZe5yYWrJ2ds137.jpg')
        ;

        $movie->getRating()
            ->setHated(100)
            ->setLoved(100)
            ->setPercentage(86)
            ->setWatching(2)
            ->setVotes(32184)
        ;

        $t1 = new Torrent();
        $t1->setMovie($movie)
            ->setLanguage('en')
            ->setQuality('1080p')
            ->setProvider('YTS')
            ->setSize(1825361101)
            ->setPeer(257)
            ->setSeed(630)
            ->setFilesize('1.70 GB')
            ->setUrl('magnet:?xt=urn:btih:FE1E1069DE410FB44157F02B4F6655DDE99621C6&tr=udp://glotorrents.pw:6969/announce&tr=udp://tracker.opentrackr.org:1337/announce&tr=udp://torrent.gresille.org:80/announce&tr=udp://tracker.openbittorrent.com:80&tr=udp://tracker.coppersurfer.tk:6969&tr=udp://tracker.leechers-paradise.org:6969&tr=udp://p4p.arenabg.ch:1337&tr=udp://tracker.internetwarriors.net:1337')
        ;

        $t2 = new Torrent();
        $t2->setMovie($movie)
            ->setLanguage('en')
            ->setQuality('720p')
            ->setProvider('YTS')
            ->setSize(958356521)
            ->setPeer(61)
            ->setSeed(166)
            ->setFilesize('913.96 MB')
            ->setUrl('magnet:?xt=urn:btih:8AD627B1DD1FFA6B46E977796A42D34DDF3A0DDE&tr=udp://glotorrents.pw:6969/announce&tr=udp://tracker.opentrackr.org:1337/announce&tr=udp://torrent.gresille.org:80/announce&tr=udp://tracker.openbittorrent.com:80&tr=udp://tracker.coppersurfer.tk:6969&tr=udp://tracker.leechers-paradise.org:6969&tr=udp://p4p.arenabg.ch:1337&tr=udp://tracker.internetwarriors.net:1337')
        ;

        $manager->persist($movie);
        $manager->persist($t1);
        $manager->persist($t2);

        $manager->flush();
    }

    public function load2(ObjectManager $manager)
    {
        $movie = new Movie();

        $movie
            ->setImdb('tt0167261')
            ->setTitle('The Lord of the Rings: The Two Towers')
            ->setSynopsis('Frodo and Sam are trekking to Mordor to destroy the One Ring of Power while Gimli, Legolas and Aragorn search for the orc-captured Merry and Pippin. All along, nefarious wizard Saruman awaits the Fellowship members at the Orthanc Tower in Isengard.')
            ->setReleased(1040169600)
            ->setCertification('PG-13')
            ->setYear('2002')
            ->setGenres(["action", "adventure", "fantasy"])
            ->setRuntime('179')
            ->setTrailer('http://youtube.com/watch?v=cvCktPUwkW0')
        ;


        $movie->getImages()
            ->setBanner('http://image.tmdb.org/t/p/w500/wf3v0Pn09jnT5HSaYal7Ami3bdA.jpg')
            ->setFanart('http://image.tmdb.org/t/p/w500/9BUvLUz1GhbNpcyQRyZm1HNqMq4.jpg')
            ->setPoster('http://image.tmdb.org/t/p/w500/wf3v0Pn09jnT5HSaYal7Ami3bdA.jpg')
        ;

        $movie->getRating()
            ->setHated(100)
            ->setLoved(100)
            ->setPercentage(87)
            ->setWatching(2)
            ->setVotes(31892)
        ;

        $t1 = new Torrent();
        $t1->setMovie($movie)
            ->setLanguage('en')
            ->setQuality('1080p')
            ->setProvider('YTS')
            ->setSize(3221225472)
            ->setPeer(171)
            ->setSeed(670)
            ->setFilesize('3.00 GB')
            ->setUrl('magnet:?xt=urn:btih:EBDA5B39978F58B50B2666A808A11C971A9CF080&tr=udp://glotorrents.pw:6969/announce&tr=udp://tracker.opentrackr.org:1337/announce&tr=udp://torrent.gresille.org:80/announce&tr=udp://tracker.openbittorrent.com:80&tr=udp://tracker.coppersurfer.tk:6969&tr=udp://tracker.leechers-paradise.org:6969&tr=udp://p4p.arenabg.ch:1337&tr=udp://tracker.internetwarriors.net:1337')
        ;

        $t2 = new Torrent();
        $t2->setMovie($movie)
            ->setLanguage('en')
            ->setQuality('720p')
            ->setProvider('YTS')
            ->setSize(1610612736)
            ->setPeer(65)
            ->setSeed(257)
            ->setFilesize('1.50 GB')
            ->setUrl('magnet:?xt=urn:btih:2186007B26ACE9270FD6C9658213D081C698DC22&tr=udp://glotorrents.pw:6969/announce&tr=udp://tracker.opentrackr.org:1337/announce&tr=udp://torrent.gresille.org:80/announce&tr=udp://tracker.openbittorrent.com:80&tr=udp://tracker.coppersurfer.tk:6969&tr=udp://tracker.leechers-paradise.org:6969&tr=udp://p4p.arenabg.ch:1337&tr=udp://tracker.internetwarriors.net:1337')
        ;

        $manager->persist($movie);
        $manager->persist($t1);
        $manager->persist($t2);

        $manager->flush();
    }

    public function load3(ObjectManager $manager)
    {
        $movie = new Movie();

        $movie
            ->setImdb('tt0241527')
            ->setTitle('Harry Potter and the Philosopher\'s Stone')
            ->setSynopsis('Harry Potter has lived under the stairs at his aunt and uncle\'s house his whole life. But on his 11th birthday, he learns he\'s a powerful wizard -- with a place waiting for him at the Hogwarts School of Witchcraft and Wizardry. As he learns to harness his newfound powers with the help of the school\'s kindly headmaster, Harry uncovers the truth about his parents\' deaths -- and about the villain who\'s to blame.')
            ->setReleased(1005868800)
            ->setCertification('PG')
            ->setYear('2001')
            ->setGenres(["adventure", "fantasy", "family"])
            ->setRuntime('152')
            ->setTrailer('http://youtube.com/watch?v=PbdM1db3JbY')
        ;

        $movie->getImages()
            ->setBanner('http://image.tmdb.org/t/p/w500/gHPtCmMeDqjaGqnMrWGDmD3nKd2.jpg')
            ->setFanart('http://image.tmdb.org/t/p/w500/hziiv14OpD73u9gAak4XDDfBKa2.jpg')
            ->setPoster('http://image.tmdb.org/t/p/w500/gHPtCmMeDqjaGqnMrWGDmD3nKd2.jpg')
        ;

        $movie->getRating()
            ->setHated(100)
            ->setLoved(100)
            ->setPercentage(81)
            ->setWatching(0)
            ->setVotes(31841)
        ;

        $t1 = new Torrent();
        $t1->setMovie($movie)
            ->setLanguage('en')
            ->setQuality('1080p')
            ->setProvider('YTS')
            ->setSize(1256277934)
            ->setPeer(433)
            ->setSeed(1315)
            ->setFilesize('1.17 GB')
            ->setUrl('magnet:?xt=urn:btih:B47882A62EEDEC7767AA86B7A866F1DD846C5357&tr=udp://glotorrents.pw:6969/announce&tr=udp://tracker.opentrackr.org:1337/announce&tr=udp://torrent.gresille.org:80/announce&tr=udp://tracker.openbittorrent.com:80&tr=udp://tracker.coppersurfer.tk:6969&tr=udp://tracker.leechers-paradise.org:6969&tr=udp://p4p.arenabg.ch:1337&tr=udp://tracker.internetwarriors.net:1337')
        ;

        $t2 = new Torrent();
        $t2->setMovie($movie)
            ->setLanguage('en')
            ->setQuality('720p')
            ->setProvider('YTS')
            ->setSize(576716800)
            ->setPeer(138)
            ->setSeed(590)
            ->setFilesize('550.00 MB')
            ->setUrl('magnet:?xt=urn:btih:C483A8C04C800EB55EF652CAB3439F0D55DB475C&tr=udp://glotorrents.pw:6969/announce&tr=udp://tracker.opentrackr.org:1337/announce&tr=udp://torrent.gresille.org:80/announce&tr=udp://tracker.openbittorrent.com:80&tr=udp://tracker.coppersurfer.tk:6969&tr=udp://tracker.leechers-paradise.org:6969&tr=udp://p4p.arenabg.ch:1337&tr=udp://tracker.internetwarriors.net:1337')
        ;

        $manager->persist($movie);
        $manager->persist($t1);
        $manager->persist($t2);

        $manager->flush();
    }
}
