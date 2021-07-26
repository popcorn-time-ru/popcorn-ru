<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class CacheErrorListener implements EventSubscriberInterface
{
    const CACHE_TIME = 3600 * 12;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'Mark404Cached',
        ];
    }

    public function Mark404Cached(ExceptionEvent $event)
    {
        if ($event->getThrowable() instanceof NotFoundHttpException) {
            $response = new JsonResponse(array('message' => "Not Found", 'code' => 404), 404);
            $response->setSharedMaxAge(self::CACHE_TIME);
            $event->setResponse($response);
        }
    }
}
