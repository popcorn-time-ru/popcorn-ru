<?php

namespace App\Processors;

use Interop\Queue\Processor;

abstract class AbstractProcessor implements Processor
{
    protected function catchRequestException($e): string
    {
        if ($e->getResponse()) {
            echo $e->getMessage().PHP_EOL;
            if ($e->getResponse()->getStatusCode() > 500) {
                // 503 - it's temporary
                return self::REQUEUE;
            }
            if ($e->getResponse()->getStatusCode() > 400) {
                // 404 - some happens
                return self::ACK;
            }
            return self::ACK;
        }
        // no response - it's temporary
        echo $e->getMessage().PHP_EOL;
        return self::REQUEUE;
    }
}
