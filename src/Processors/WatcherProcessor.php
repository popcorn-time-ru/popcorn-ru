<?php

namespace App\Processors;

use App\Repository\MovieRepository;
use App\Repository\ShowRepository;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

class WatcherProcessor implements TopicSubscriberInterface, Processor
{
    public const TOPIC = 'watcherUpdate';

    #[Required] public MovieRepository $movieRepository;
    #[Required] public ShowRepository $showRepository;
    #[Required] public LoggerInterface $logger;
    
    public function process(Message $message, Context $context): string
    {
        try {
            $data = JSON::decode($message->getBody());
            if (empty($data['type']) || empty($data['imdb']) || !isset($data['watching'])) {
                $this->logger->error('Incorrect data', $data);
                return self::REJECT;
            }

            $repo = $data['type'] === 'movie' ? $this->movieRepository : $this->showRepository;

            $media = $repo->findByImdb($data['imdb']);
            if ($media) {
                $media->getRating()->setWatching($data['watching']);
                $repo->flush();
            }

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
