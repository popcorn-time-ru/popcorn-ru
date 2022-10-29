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
use Symfony\Contracts\Service\Attribute\Required;

class TorrentActiveProcessor implements TopicSubscriberInterface, Processor
{
    public const TOPIC = 'torrentActive';

    #[Required] public TorrentService $torrentService;
    #[Required] public LoggerInterface $logger;

    public function process(Message $message, Context $context): string
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

    public static function getSubscribedTopics(): string
    {
        return self::TOPIC;
    }
}
