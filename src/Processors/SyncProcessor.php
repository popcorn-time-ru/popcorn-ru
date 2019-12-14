<?php

namespace App\Processors;

use App\Entity\BaseTorrent;
use App\Entity\Movie;
use App\Entity\Show;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use App\Service\TmdbExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\RequestException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;

class SyncProcessor implements TopicSubscriberInterface, Processor
{
    public const TOPIC = 'sync';

    /** @var TmdbExtractor */
    private $extractor;

    /** @var ProducerInterface */
    private $producer;

    /** @var TorrentRepository */
    private $torrentRepository;

    /** @var MovieRepository */
    private $movieRepository;

    /** @var ShowRepository */
    private $showRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        TmdbExtractor $extractor,
        TorrentRepository $torrentRepository,
        MovieRepository $movieRepository,
        ShowRepository $showRepository,
        ProducerInterface $producer,
        LoggerInterface $logger)
    {
        $this->producer = $producer;
        $this->logger = $logger;
        $this->torrentRepository = $torrentRepository;
        $this->movieRepository = $movieRepository;
        $this->showRepository = $showRepository;
        $this->extractor = $extractor;
        $this->em = $em;
    }

    public function process(Message $message, Context $context)
    {
        try {
            $data = JSON::decode($message->getBody());
            if (empty($data['type']) || empty($data['id'])) {
                $this->logger->error('Incorrect data', $data);
                return self::REJECT;
            }

            if ($data['type'] === 'torrent') {
                /** @var BaseTorrent $torrent */
                $torrent = $this->torrentRepository->find($data['id']);
                $topicMessage = new \Enqueue\Client\Message(JSON::encode([
                    'spider' => $torrent->getProvider(),
                    'topicId' => $torrent->getProviderExternalId(),
                    'seed' => 0,
                    'leech' => 0,
                ]));
                $topicMessage->setDelay(random_int(120, 3600));
                $this->producer->sendEvent(TopicProcessor::TOPIC, $topicMessage);
                $torrent->check();
            }

            if ($data['type'] === 'movie') {
                /** @var Movie $movie */
                $movie = $this->movieRepository->find($data['id']);
                $this->extractor->updateRating($movie);
                $movie->sync();
            }
            if ($data['type'] === 'show') {
                /** @var Show $show */
                $show = $this->showRepository->find($data['id']);
                $this->extractor->updateRating($show);
                $show->sync();
            }

            $this->em->flush();
            return self::ACK;
        } catch (RequestException $e) {
            if ($e->getResponse()) {
                echo $e->getMessage().PHP_EOL;
                return self::ACK;
            }
            echo $e->getMessage().PHP_EOL;
            return self::REQUEUE;
        } catch (\Exception $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString();
        }
        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return self::TOPIC;
    }
}
