<?php

namespace Flowti\Tests\Service;

use Flowti\ZabbixBundle\Service\FlowtiZabbixClient;
use PHPUnit\Framework\TestCase;
//use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FlowtiZabbixClientTest extends TestCase
{

    private $client;

    protected function setUp(): void
    {
        $parameterBagInterface = $this->createMock(ParameterBagInterface::class);
        //$logger = $this->createMock(LoggerInterface::class);
        $logger = new Logger('test');

        $dt['flowti_zabbix.client.host'] = getenv('host');
        $dt['flowti_zabbix.client.username'] = getenv('username');
        $dt['flowti_zabbix.client.password'] = getenv('password');
        $parameterBagInterface->expects($this->once())
            ->method('all')
            ->willReturn($dt);
        //dd($parameterBagInterface);
        $this->client = new FlowtiZabbixClient($parameterBagInterface, $logger);
    }

    public function testGetTrigger()
    {
        $ret = $this->client->getTrigger([629272]);
        $this->assertEquals('629272', $ret[0]['triggerid']);
    }

    public function testGetHost()
    {
        $ret = $this->client->getHost('srvdata02rj-q01147');
        $this->assertEquals('11765', $ret[0]['hostid']);
    }

    public function testGetEvent()
    {
        $ret = $this->client->getEvent([91217179]);
        $this->assertEquals('91217179', $ret[0]['eventid']);
    }

    public function testGetHostGroups()
    {
        $ret = $this->client->getHostGroups();
        $this->assertEquals('1109', $ret[0]['groupid']);
    }

    public function testGetHosts()
    {
        $ret = $this->client->getHosts([729]);
        $this->assertEquals('11108', $ret[0]['hostid']);
    }

    public function testGetApplications()
    {
        $ret = $this->client->getApplications([11124]);
        $this->assertEquals('21049', $ret[0]['applicationid']);
    }
    
    public function testGetItems()
    {
        $ret = $this->client->getItems([11124],[21049]);
        $this->assertEquals('180519', $ret[0]['itemid']);
    }
    

    
}