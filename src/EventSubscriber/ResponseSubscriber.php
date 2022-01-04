<?php
namespace Flowti\ZabbixBundle\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseSubscriber implements EventSubscriberInterface
{
    private ParameterBagInterface $parameterBag;
    
    public function __construct(ParameterBagInterface $parameterBag) 
    {
        $this->parameterBag = $parameterBag;    
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['addZabbix', 0],
            ],
        ];
    }

    public function addZabbix(ResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('X-Header-Endpoint', $this->parameterBag->get('flowti_zabbix.zabbix_rest_endpoint'));
    }

}