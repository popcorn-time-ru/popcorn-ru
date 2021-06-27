<?php

namespace App\Processors;

use App\Entity\Torrent\ShowTorrent;
use App\Service\EpisodeService;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class TorrentFilesLinkProcessor extends AbstractProcessor implements TopicSubscriberInterface
{
    public const TOPIC = 'linkTorrentFiles';

    /** @var EpisodeService */
    protected $episodes;

    /** @var LoggerInterface */
    private $logger;

    /** @var ProducerInterface */
    private $producer;

    /**
     * ShowTorrentProducer constructor.
     *
     * @param EpisodeService  $episodes
     * @param LoggerInterface $logger
     */
    public function __construct(EpisodeService $episodes, ProducerInterface $producer, LoggerInterface $logger)
    {
        $this->episodes = $episodes;
        $this->logger = $logger;
        $this->producer = $producer;
    }

    public function process(Message $message, Context $context)
    {
        try {
            $data = JSON::decode($message->getBody());
            if (empty($data['torrentId'])) {
                $this->logger->error('Not Set TorrentId', $data);
                return self::REJECT;
            }
            $id = Uuid::fromString($data['torrentId']);
            $this->episodes->link($id);
            $this->producer->sendEvent(TorrentActiveProcessor::TOPIC, JSON::encode($data));

            return self::ACK;
        } catch (RequestException $e) {
            return $this->catchRequestException($e);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return self::TOPIC;
    }
}
