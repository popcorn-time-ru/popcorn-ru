<?php

namespace App\Spider;

use App\Entity\Anime;
use App\Entity\Episode\Episode;
use App\Entity\Movie;
use App\Entity\Torrent\AnimeTorrent;
use App\Entity\Torrent\BaseTorrent;
use App\Entity\Torrent\EpisodeTorrent;
use App\Entity\Torrent\MovieTorrent;
use App\Entity\Show;
use App\Entity\Torrent\ShowTorrent;
use App\Service\EpisodeService;
use App\Service\ParseHelperService;
use App\Service\TorrentService;
use DateTime;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractSpider implements SpiderInterface
{
    /** @required */
    public TorrentService $torrentService;

    /** @required */
    public EpisodeService $episodeService;

    /** @required */
    public ParseHelperService $parseHelper;

    /** @required */
    public LoggerInterface $logger;

    protected $context;

    public function useTor(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    public function getPriority(BaseTorrent $torrent): int
    {
        return 0;
    }

    public function getSource(BaseTorrent $torrent): string
    {
        return '';
    }

    protected function ruStrToTime(string $format, string $time): DateTime
    {
        $ru = ['Янв', 'Фев', 'Мар', 'Апр', 'Июн', 'Май', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
        $en = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct',' nov', 'dec'];
        $time = str_replace($ru, $en, $time);
        return DateTime::createFromFormat($format, $time);
    }

    protected function getTorrentByImdb(string $topicId, string $imdb): ?BaseTorrent
    {
        $media = $this->torrentService->getMediaByImdb($imdb);
        if (!$media) {
            return null;
        }
        $newTorrent = null;
        if ($media instanceof Movie) {
            $newTorrent = new MovieTorrent();
            $newTorrent->setMovie($media);
        }
        if ($media instanceof Show) {
            $newTorrent = new ShowTorrent();
            $newTorrent->setShow($media);
        }

        return $this->torrentService->findExistOrCreateTorrent($this->getName(), $topicId, $newTorrent);
    }

    protected function getEpisodeTorrentByImdb(string $topicId, string $imdb, int $s, int $e)
    {
        $media = $this->torrentService->getMediaByImdb($imdb);
        if (!($media instanceof Show)) {
            return null;
        }
        $episode = $this->episodeService->getEpisode($media, $s, $e);
        if (!($episode instanceof Episode)) {
            return null;
        }
        $newTorrent = new EpisodeTorrent();
        $newTorrent->setEpisode($episode);
        return $this->torrentService->findExistOrCreateTorrent(
            $this->getName(),
            $topicId,
            $newTorrent
        );
    }

    protected function hackForReleaserLang(BaseTorrent $torrent, Crawler $post)
    {
        // Release group put all they content with incorrect lang
        if (stripos($torrent->getProviderTitle(), 'mircrew') !== false) {
            $torrent->setLanguage('it');
        }
        if (preg_match('#ita|spa#i', $torrent->getProviderTitle())) {
            if (stripos($post->html(), 'mircrew') !== false) {
                $torrent->setLanguage('it');
            }
        }
    }

    protected function getTorrentByKitsu(string $topicId, string $kitsu): ?BaseTorrent
    {
        $anime = $this->torrentService->getMediaByKitsu($kitsu);
        if (!($anime instanceof Anime)) {
            return null;
        }
        $newTorrent = new AnimeTorrent();
        $newTorrent->setAnime($anime);

        return $this->torrentService->findExistOrCreateTorrent($this->getName(), $topicId, $newTorrent);
    }

    protected function getEpisodeTorrentByKitsu(string $topicId, string $kitsu, int $s, int $e)
    {
        $anime = $this->torrentService->getMediaByKitsu($kitsu);
        var_dump($anime->getImdb());die();
        $episode = $this->episodeService->getEpisode($anime, $s, $e);
        if (!($episode instanceof Episode)) {
            return null;
        }
        $newTorrent = new EpisodeTorrent();
        $newTorrent->setEpisode($episode);
        return $this->torrentService->findExistOrCreateTorrent(
            $this->getName(),
            $topicId,
            $newTorrent
        );
    }

    protected function langName2IsoCode(string $lang): string
    {
        static $languagesMap = [
            'Spanish' => 'es',
            'Greek' => 'el',
            'Japanese' => 'ja',
            'Other' => '',
            'Other / Multiple' => '',
        ];
        static $languages = [
            'aa' => 'Afar',
            'ab' => 'Abkhaz',
            'ae' => 'Avestan',
            'af' => 'Afrikaans',
            'ak' => 'Akan',
            'am' => 'Amharic',
            'an' => 'Aragonese',
            'ar' => 'Arabic',
            'as' => 'Assamese',
            'av' => 'Avaric',
            'ay' => 'Aymara',
            'az' => 'Azerbaijani',
            'ba' => 'Bashkir',
            'be' => 'Belarusian',
            'bg' => 'Bulgarian',
            'bh' => 'Bihari',
            'bi' => 'Bislama',
            'bm' => 'Bambara',
            'bn' => 'Bengali',
            'bo' => 'Tibetan Standard, Tibetan, Central',
            'br' => 'Breton',
            'bs' => 'Bosnian',
            'ca' => 'Catalan; Valencian',
            'ce' => 'Chechen',
            'ch' => 'Chamorro',
            'co' => 'Corsican',
            'cr' => 'Cree',
            'cs' => 'Czech',
            'cu' => 'Old Church Slavonic, Church Slavic, Church Slavonic, Old Bulgarian, Old Slavonic',
            'cv' => 'Chuvash',
            'cy' => 'Welsh',
            'da' => 'Danish',
            'de' => 'German',
            'dv' => 'Divehi; Dhivehi; Maldivian;',
            'dz' => 'Dzongkha',
            'ee' => 'Ewe',
            'el' => 'Greek, Modern',
            'en' => 'English',
            'eo' => 'Esperanto',
            'es' => 'Spanish; Castilian',
            'et' => 'Estonian',
            'eu' => 'Basque',
            'fa' => 'Persian',
            'ff' => 'Fula; Fulah; Pulaar; Pular',
            'fi' => 'Finnish',
            'fj' => 'Fijian',
            'fo' => 'Faroese',
            'fr' => 'French',
            'fy' => 'Western Frisian',
            'ga' => 'Irish',
            'gd' => 'Scottish Gaelic; Gaelic',
            'gl' => 'Galician',
            'gn' => 'GuaranÃƒÂ­',
            'gu' => 'Gujarati',
            'gv' => 'Manx',
            'ha' => 'Hausa',
            'he' => 'Hebrew (modern)',
            'hi' => 'Hindi',
            'ho' => 'Hiri Motu',
            'hr' => 'Croatian',
            'ht' => 'Haitian; Haitian Creole',
            'hu' => 'Hungarian',
            'hy' => 'Armenian',
            'hz' => 'Herero',
            'ia' => 'Interlingua',
            'id' => 'Indonesian',
            'ie' => 'Interlingue',
            'ig' => 'Igbo',
            'ii' => 'Nuosu',
            'ik' => 'Inupiaq',
            'io' => 'Ido',
            'is' => 'Icelandic',
            'it' => 'Italian',
            'iu' => 'Inuktitut',
            'ja' => 'Japanese (ja)',
            'jv' => 'Javanese (jv)',
            'ka' => 'Georgian',
            'kg' => 'Kongo',
            'ki' => 'Kikuyu, Gikuyu',
            'kj' => 'Kwanyama, Kuanyama',
            'kk' => 'Kazakh',
            'kl' => 'Kalaallisut, Greenlandic',
            'km' => 'Khmer',
            'kn' => 'Kannada',
            'ko' => 'Korean',
            'kr' => 'Kanuri',
            'ks' => 'Kashmiri',
            'ku' => 'Kurdish',
            'kv' => 'Komi',
            'kw' => 'Cornish',
            'ky' => 'Kirghiz, Kyrgyz',
            'la' => 'Latin',
            'lb' => 'Luxembourgish, Letzeburgesch',
            'lg' => 'Luganda',
            'li' => 'Limburgish, Limburgan, Limburger',
            'ln' => 'Lingala',
            'lo' => 'Lao',
            'lt' => 'Lithuanian',
            'lu' => 'Luba-Katanga',
            'lv' => 'Latvian',
            'mg' => 'Malagasy',
            'mh' => 'Marshallese',
            'mi' => 'Maori',
            'mk' => 'Macedonian',
            'ml' => 'Malayalam',
            'mn' => 'Mongolian',
            'mr' => 'Marathi (Mara?hi)',
            'ms' => 'Malay',
            'mt' => 'Maltese',
            'my' => 'Burmese',
            'na' => 'Nauru',
            'nb' => 'Norwegian BokmÃƒÂ¥l',
            'nd' => 'North Ndebele',
            'ne' => 'Nepali',
            'ng' => 'Ndonga',
            'nl' => 'Dutch',
            'nn' => 'Norwegian Nynorsk',
            'no' => 'Norwegian',
            'nr' => 'South Ndebele',
            'nv' => 'Navajo, Navaho',
            'ny' => 'Chichewa; Chewa; Nyanja',
            'oc' => 'Occitan',
            'oj' => 'Ojibwe, Ojibwa',
            'om' => 'Oromo',
            'or' => 'Oriya',
            'os' => 'Ossetian, Ossetic',
            'pa' => 'Panjabi, Punjabi',
            'pi' => 'Pali',
            'pl' => 'Polish',
            'ps' => 'Pashto, Pushto',
            'pt' => 'Portuguese',
            'qu' => 'Quechua',
            'rm' => 'Romansh',
            'rn' => 'Kirundi',
            'ro' => 'Romanian, Moldavian, Moldovan',
            'ru' => 'Russian',
            'rw' => 'Kinyarwanda',
            'sa' => 'Sanskrit (Sa?sk?ta)',
            'sc' => 'Sardinian',
            'sd' => 'Sindhi',
            'se' => 'Northern Sami',
            'sg' => 'Sango',
            'si' => 'Sinhala, Sinhalese',
            'sk' => 'Slovak',
            'sl' => 'Slovene',
            'sm' => 'Samoan',
            'sn' => 'Shona',
            'so' => 'Somali',
            'sq' => 'Albanian',
            'sr' => 'Serbian',
            'ss' => 'Swati',
            'st' => 'Southern Sotho',
            'su' => 'Sundanese',
            'sv' => 'Swedish',
            'sw' => 'Swahili',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'tg' => 'Tajik',
            'th' => 'Thai',
            'ti' => 'Tigrinya',
            'tk' => 'Turkmen',
            'tl' => 'Tagalog',
            'tn' => 'Tswana',
            'to' => 'Tonga (Tonga Islands)',
            'tr' => 'Turkish',
            'ts' => 'Tsonga',
            'tt' => 'Tatar',
            'tw' => 'Twi',
            'ty' => 'Tahitian',
            'ug' => 'Uighur, Uyghur',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek',
            've' => 'Venda',
            'vi' => 'Vietnamese',
            'vo' => 'VolapÃƒÂ¼k',
            'wa' => 'Walloon',
            'wo' => 'Wolof',
            'xh' => 'Xhosa',
            'yi' => 'Yiddish',
            'yo' => 'Yoruba',
            'za' => 'Zhuang, Chuang',
            'zh' => 'Chinese',
            'zu' => 'Zulu',
        ];

        if (isset($languagesMap[$lang])) {
            return $languagesMap[$lang];
        }

        $search = array_search($lang, $languages);

        if ($search) {
            return $search;
        }

        $this->logger->warning('Unknown Language', $this->context + ['lang' => $lang]);

        return '';
    }

    public function approximateSize(string $size): int
    {
        preg_match('#([\d.]+)\W*([KMG]?B)#i', $size, $m);
        if (!$m) {
            return 0;
        }
        $size = (float) $m[1];
        switch (strtoupper($m[2])) {
            case 'GB':
                $size *= 1024;
            case 'MB':
                $size *= 1024;
            case 'KB':
                $size *= 1024;
        }

        return (int)$size;
    }
}
