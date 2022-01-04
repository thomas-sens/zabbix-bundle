<?php
namespace Flowti\ZabbixBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['addSecurityHeaders', 0],
            ],
        ];
    }

    public function addSecurityHeaders(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('X-Header-Set-By', 'My custom bundle');
    }
}