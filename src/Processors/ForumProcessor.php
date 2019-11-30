<?php

namespace App\Processors;

use App\Service\SpiderSelector;
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
            $generator = $spider->getPage($data['forumId'], $data['page']);
            foreach ($generator as $topic) {
                $message = new \Enqueue\Client\Message(JSON::encode([
                    'spider' => $data['spider'],
                    'topicId' => $topic
                ]));
                $message->setDelay(random_int(60, 300));
                $this->producer->sendEvent(TopicProcessor::TOPIC, $message);
            }

            if ($generator->getReturn()) {
                $data['page']++;
                $message = new \Enqueue\Client\Message(JSON::encode($data));
                $message->setDelay(random_int(600, 1200));
                $this->producer->sendEvent(ForumProcessor::TOPIC, $message);
            }

            return self::ACK;
        } catch (GuzzleException $e) {
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
