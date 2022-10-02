<?php

namespace App\Logging;

use Monolog\Processor\ProcessorInterface;

class SentryContextProcessor implements ProcessorInterface
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        foreach ($record['context'] as $key => $val) {
            if ($key === 'extra') {
                continue;
            }
            $record['context']['extra'][$key] = $val;
        }
        return $record;
    }
}
