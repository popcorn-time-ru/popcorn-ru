<?php

namespace App\Processors;

use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class ShowTorrentProducer implements TopicSubscriberInterface, Processor
{
    public const TOPIC = 'linkShowTorrent';

    public function process(Message $message, Context $context)
    {
        $data = JSON::decode($message->getBody());

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return self::TOPIC;
    }
}
