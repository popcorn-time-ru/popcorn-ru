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
use Symfony\Contracts\Service\Attribute\Required;

class TopicProcessor extends AbstractProcessor implements TopicSubscriberInterface
{
    public const TOPIC = 'getTopic';

    #[Required] public SpiderSelector $selector;
    #[Required] public LoggerInterface $logger;

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
            $spider->getTopic(new TopicDto(
                $data['topicId'],
                $data['seed'],
                $data['leech']
            ));

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
