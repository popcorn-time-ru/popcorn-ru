<?php

namespace App\Processors;

use App\Service\SpiderSelector;
use App\Spider\Dto\TopicDto;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Psr\Log\LoggerInterface;

class TopicProcessor implements TopicSubscriberInterface, Processor
{
    public const TOPIC = 'getTopic';

    /** @var SpiderSelector */
    protected $selector;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(SpiderSelector $selector, LoggerInterface $logger)
    {
        $this->selector = $selector;
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
            $spider->getTopic(new TopicDto(
                $data['topicId'],
                $data['seed'],
                $data['leech']
            ));

            return self::ACK;
        } catch (RequestException $e) {
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
