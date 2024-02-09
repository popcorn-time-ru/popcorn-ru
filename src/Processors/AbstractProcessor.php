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
                sleep(10);
                // 503 - it's temporary
                return rand(1, 10) == 5 ? self::ACK : self::REQUEUE;
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
