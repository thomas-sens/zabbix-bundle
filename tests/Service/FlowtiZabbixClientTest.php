<?php

namespace Flowti\Tests\Service;

use Flowti\ZabbixBundle\Model\Chart;
use Flowti\ZabbixBundle\Model\HostGroup;
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

    public function testGetHostGroups()
    {

        $params ='{
        "output": ["groupid","name"],
        "searchWildcardsEnabled": true,
        "searchByAny": true,
        "search": {
            "name": [
                "q10022-gn-Hospital Regina",
                "*Cluster*"
                ]
            }
        }';

        $hostGroup = new HostGroup($this->client);
        $ret = $hostGroup->get($params);
        $this->assertIsArray($ret);
        $this->assertArrayHasKey(0,$ret);
    }

    public function testGetHostGroupsDeprecated()
    {
        $ret = $this->client->getHostGroups();
        $this->assertIsArray($ret);
        $this->assertArrayHasKey(0,$ret);
    }

    public function testGetChart()
    {
        $chart = new Chart($this->client);
        $ret = $chart->get([179745,179746,179750], 1080, 200, 'now-3M', 'now');
        $this->assertFileExists($ret);
    }

    public function testGetChartDeprecated()
    {
        $ret = $this->client->getChart([179745,179746,179750], 1080, 200, 'now-3M', 'now');
        $this->assertFileExists($ret);
    }

    public function testGetHistoryDeprecated()
    {
        $ret = $this->client->getHistory([1702472], 15, 1, 100);
        $this->assertEquals('1702472', $ret[0]['itemid']);
    }

    public function testGetTriggerDeprecated()
    {
        $ret = $this->client->getTrigger([629272]);
        $this->assertEquals('629272', $ret[0]['triggerid']);
    }

    public function testGetHostDeprecated()
    {
        $ret = $this->client->getHost('srvdata02rj-q01147');
        $this->assertEquals('11765', $ret[0]['hostid']);
    }

    public function testGetEventDeprecated()
    {
        $ret = $this->client->getEvent([91217179]);
        $this->assertEquals('91217179', $ret[0]['eventid']);
    }

    public function testGetHostsDeprecated()
    {
        $ret = $this->client->getHosts([729]);
        $this->assertEquals('11108', $ret[0]['hostid']);
    }

    public function testGetApplicationsDeprecated()
    {
        $ret = $this->client->getApplications([11124]);
        $this->assertEquals('21049', $ret[0]['applicationid']);
    }
    
    public function testGetItemsDeprecated()
    {
        $ret = $this->client->getItems([11124],[21049]);
        $this->assertEquals('180519', $ret[0]['itemid']);
    }

}