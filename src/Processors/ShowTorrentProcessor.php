<?php

namespace App\Processors;

use App\Service\EpisodeService;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\RequestException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\Service\Attribute\Required;

class ShowTorrentProcessor extends AbstractProcessor implements TopicSubscriberInterface
{
    public const TOPIC = 'linkShowTorrent';

    #[Required] public EpisodeService $episodes;
    #[Required] public LoggerInterface $logger;
    #[Required] public ProducerInterface $producer;

    public function process(Message $message, Context $context): string
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

    public static function getSubscribedTopics(): string
    {
        return self::TOPIC;
    }
}
