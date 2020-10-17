<?php

namespace App\Processors;

use App\Service\SpiderSelector;
use App\Spider\Dto\ForumDto;
use App\Spider\Dto\TopicDto;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\GuzzleException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;

class ForumProcessor implements TopicSubscriberInterface, Processor
{
    public const TOPIC = 'getPage';

    /** @var SpiderSelector */
    protected $selector;

    /** @var LoggerInterface */
    private $logger;

    /** @var ProducerInterface */
    private $producer;

    public function __construct(SpiderSelector $selector, ProducerInterface $producer, LoggerInterface $logger)
    {
        $this->selector = $selector;
        $this->producer = $producer;
        $this->logger = $logger;
    }

    public function process(Message $message, Context $context)
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
            $generator = $spider->getPage(new ForumDto($data['forumId'], $data['page'], $data['last'] ?? null));
            foreach ($generator as $topic) {
                if ($topic instanceof ForumDto) {
                    $nextForumMessage = new \Enqueue\Client\Message(JSON::encode([
                        'spider' => $data['spider'],
                        'forumId' => $topic->id,
                        'page' => $topic->page,
                        'last' => $topic->last,
                    ]));
                    $nextForumMessage->setDelay($topic->delay);
                    $this->producer->sendEvent(self::TOPIC, $nextForumMessage);
                }
                if ($topic instanceof TopicDto) {
                    $topicMessage = new \Enqueue\Client\Message(JSON::encode([
                        'spider' => $data['spider'],
                        'topicId' => $topic->id,
                        'seed' => $topic->seed,
                        'leech' => $topic->leech,
                    ]));
                    $topicMessage->setDelay($topic->delay);
                    $this->producer->sendEvent(TopicProcessor::TOPIC, $topicMessage);
                }
            }

            return self::ACK;
        } catch (GuzzleException $e) {
            if ($e->getResponse()) {
                echo $e->getMessage().PHP_EOL;
                return self::ACK;
            }
            echo $e->getMessage().PHP_EOL;
            return self::REQUEUE;
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
