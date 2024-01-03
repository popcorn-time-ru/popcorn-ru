<?php

namespace App\Processors;

use App\Entity\Torrent\BaseTorrent;
use App\Repository\TorrentRepository;
use App\Service\EpisodeService;
use App\Service\SpiderSelector;
use App\Service\TorrentService;
use App\Spider\DownloadInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\RequestException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\Service\Attribute\Required;

class DownloadTorrentProcessor extends AbstractProcessor implements TopicSubscriberInterface
{
    public const TOPIC = 'downloadTorrent';

    #[Required] public SpiderSelector $selector;
    #[Required] public TorrentRepository $torrentRepository;
    #[Required] public LoggerInterface $logger;
    #[Required] public ProducerInterface $producer;

    public function process(Message $message, Context $context): string
    {
        try {
            $data = JSON::decode($message->getBody());
            if (empty($data['spider'])) {
                $this->logger->error('Not Set Spider', $data);
                return self::REJECT;
            }
            $spider = $this->selector->get($data['spider']);
            if (!$spider) {
                $this->logger->error('Unknown Spider', $data);
                return self::REJECT;
            }
            if (!($spider instanceof DownloadInterface)) {
                $this->logger->error('Spider not implement DownloadInterface', $data);
                return self::REJECT;
            }
            if (empty($data['torrentId'])) {
                $this->logger->error('Not Set TorrentId', $data);
                return self::REJECT;
            }
            if (empty($data['downloadId'])) {
                $this->logger->error('Not Set DownloadId', $data);
                return self::REJECT;
            }
            /** @var BaseTorrent $torrent */
            $torrent = $this->torrentRepository->find($data['torrentId']);
            if (!$torrent) {
                $this->logger->error('Not found torrent', $data);
                return self::REJECT;
            }

            $spider->downloadTorrent($torrent, $data['downloadId']);
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
