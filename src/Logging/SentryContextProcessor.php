<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class SentryContextProcessor implements ProcessorInterface
{
    /**
     * @param LogRecord $record
     * @return LogRecord
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        foreach ($record->context as $key => $val) {
            if ($key === 'extra') {
                continue;
            }
            $record->extra[$key] = $val;
        }
        return $record;
    }
}
