<?php

namespace App\Processors;

use App\Entity\Torrent\BaseTorrent;
use App\Entity\Movie;
use App\Entity\Show;
use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use App\Repository\TorrentRepository;
use App\Service\MediaService;
use App\Service\TorrentService;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\RequestException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class SyncProcessor extends AbstractProcessor implements TopicSubscriberInterface
{
    public const TOPIC = 'sync';

    #[Required] public MediaService $extractor;
    #[Required] public ProducerInterface $producer;
    #[Required] public TorrentService $torrentService;
    #[Required] public TorrentRepository $torrentRepository;
    #[Required] public MovieRepository $movieRepository;
    #[Required] public ShowRepository $showRepository;
    #[Required] public LoggerInterface $logger;
    #[Required] public EntityManagerInterface $em;

    public function process(Message $message, Context $context): string
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
                if (!$torrent) {
                    return self::ACK;
                }
                if (!$torrent->isSynced()) {
                    $this->torrentService->deleteTorrent($torrent->getProvider(), $torrent->getProviderExternalId());
                    return self::ACK;
                }
                if ($torrent->isChecked()) {
                    return self::ACK;
                }
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
                if (!$movie) {
                    return self::ACK;
                }
                $this->extractor->updateMedia($movie);
                $movie->sync();
            }
            if ($data['type'] === 'show') {
                /** @var Show $show */
                $show = $this->showRepository->find($data['id']);
                if (!$show) {
                    return self::ACK;
                }
                $this->extractor->updateMedia($show);
                $show->sync();
            }

            $this->em->flush();
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
