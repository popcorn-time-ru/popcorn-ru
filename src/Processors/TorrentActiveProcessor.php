<?php

namespace App\Processors;

use App\Service\EpisodeService;
use App\Service\TorrentService;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\GuzzleException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class TorrentActiveProcessor implements TopicSubscriberInterface, Processor
{
    public const TOPIC = 'torrentActive';

    /** @var TorrentService */
    private TorrentService $torrentService;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * ShowTorrentProducer constructor.
     *
     * @param TorrentService  $torrentService
     * @param LoggerInterface $logger
     */
    public function __construct(TorrentService $torrentService, LoggerInterface $logger)
    {
        $this->torrentService = $torrentService;
        $this->logger = $logger;
    }

    /**
     * @param Message $message
     * @param Context $context
     * @return object|string
     */
    public function process(Message $message, Context $context): object|string
    {
        try {
            $data = JSON::decode($message->getBody());
            if (empty($data['torrentId'])) {
                $this->logger->error('Not Set TorrentId', $data);
                return self::REJECT;
            }
            $id = Uuid::fromString($data['torrentId']);
            $this->torrentService->updateActive($id);

            return self::ACK;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
        return self::ACK;
    }

    /**
     *@return string
     */
    public static function getSubscribedTopics(): string
    {
        return self::TOPIC;
    }
}
