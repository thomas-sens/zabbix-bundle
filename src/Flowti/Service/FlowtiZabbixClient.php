<?php

namespace Flowti\ZabbixBundle\Service;

use Flowti\ZabbixBundle\Model\Application;
use Flowti\ZabbixBundle\Model\Chart;
use Flowti\ZabbixBundle\Model\Event;
use Flowti\ZabbixBundle\Model\History;
use Flowti\ZabbixBundle\Model\Host;
use Flowti\ZabbixBundle\Model\HostGroup;
use Flowti\ZabbixBundle\Model\Item;
use Flowti\ZabbixBundle\Model\Trigger;
use GuzzleHttp\Client as GClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FlowtiZabbixClient
{
    private $token_auth;
    private $zbClient;
    private $zabbix_rest_endpoint_user;
    private $zabbix_rest_endpoint_pass;
    private $logger;
    private $zabbix_rest_endpoint;

    public function __construct(ParameterBagInterface $parameter, LoggerInterface $logger)
    {
        $parameters = $parameter->all();
        $this->logger = $logger;
        $this->zabbix_rest_endpoint_user = $parameters['flowti_zabbix.client.username'];
        $this->zabbix_rest_endpoint_pass = $parameters['flowti_zabbix.client.password'];
        $this->zabbix_rest_endpoint = $parameters['flowti_zabbix.client.host'];
        $this->zbClient = new GClient(['verify' => false]);
        $this->token_auth = $this->logIn(); 
    }

    public function isAutenticated()
    {
        return $this->token_auth;
    }

    public function getZabbixRestEnpointUser()
    {
        return $this->zabbix_rest_endpoint_user;
    }

    public function getZabbixRestEnpointPass()
    {
        return $this->zabbix_rest_endpoint_pass;
    }

    public function getZabbixRestEnpoint()
    {
        return $this->zabbix_rest_endpoint;
    }

    public function __destruct()
    {
        $this->logOut();
    }

    public function callEndpoint($method, $params) {
        $authToken = '"id": 0';
        $this->logger->info($method);
        if ($this->token_auth) {
            $authToken = '"id": 1, "auth": "'.$this->token_auth.'"';
        }
        $input = [
            'body' => '{ "jsonrpc": "2.0", "method": "'.$method.'", "params": '.$params.', '.$authToken.' }',
            'headers'  => ['content-type' => 'application/json'],
            'verify' => false,
            'debug' => false,
            'timeout' => 10, 
            'connect_timeout' => 10,
            'http_errors' => false,
        ];

        $response = $this->zbClient->request('POST', $this->zabbix_rest_endpoint.'/api_jsonrpc.php', $input);

        $ret = json_decode($response->getBody()->getContents(),true);

        if (!$response->getStatusCode()=='200') {
            $this->logger->error("ERRO $method ".$response->getStatusCode());
        }
        if (isset($ret['error'])) {
            $this->logger->error("ERRO $method: ".$ret['error']['message'].' - '.$ret['error']['data']);
        }

        if (isset($ret['result'])) return $ret['result'];
        return null;
    }

    private function logIn() {
        return $this->callEndpoint('user.login', '{"user": "'.$this->zabbix_rest_endpoint_user.'","password": "'.$this->zabbix_rest_endpoint_pass.'"}');
    }

    private function logOut() {
        if ($this->token_auth) {
            $this->callEndpoint('user.logout', '[]', $this->token_auth);
        }
    }




    /**
     * @deprecated use new Chart() to get(...)
     */
    public function getChart(Array $itemid, int $width = 1080, int $height = 200, String $from = 'now-1h', String $to = 'now') {
        $chart = new Chart($this);
        return $chart->get($itemid, $width, $height, $from, $to);
    }

    /**
     * @deprecated use new Event() to acknowledge($params)
     */
    public function msgZabbix(String $chamado, Array $eventids) {
        $event = new Event($this);
        return $event->acknowledge('{
            "eventids": '.json_encode($eventids).',
            "action": "4",
            "message": "Qualitor: '.$chamado.'"
        }');
    }

    /**
     * @deprecated use new Host() to get($params)
     */
    public function getHost(String $hostname) {
        $host = new Host($this);
        return $host->get('{
            "output": ["hostid","description"],
            "filter": {
                "host": [
                    "'.$hostname.'"
                ]
            },
            "selectInventory": ["os"]
        }');
    }

    /**
     * @deprecated use new Host() to get($params)
     */
    public function getHosts(Array $groupids) {
        $host = new Host($this);
        return $host->get('{
            "output": ["hostid","name","description"],
            "groupids": '.json_encode($groupids).'
        }'); 
    }

    /**
     * @deprecated use new HostGroup() to get($params)
     */
    public function getHostGroups() {
        $hostGroup = new HostGroup($this);
        return $hostGroup->get('{
            "output": ["groupid","name"],
            "real_hosts": 1
        }');
    }

    /**
     * @deprecated use new Application() to get($params)
     */
    public function getApplications(Array $hostids) {
        $application = new Application($this);
        return $application->get('{
            "output": ["applicationid","name"],
            "hostids": '.json_encode($hostids).'
        }');
    }

    /**
     * @deprecated use new Event() to get($params)
     */
    public function getEvent(Array $eventids) {
        $event = new Event($this);
        return $event->get('{
            "output": "extend",
            "selectHosts": "extend",
            "eventids": '.json_encode($eventids).'
        }');
    }

    /**
     * @deprecated use new Trigger() to get($params)
     */
    public function getTrigger(Array $triggerids) {
        $trigger = new Trigger($this);
        return $trigger->get('{
            "output": "extend",
            "selectItems": "extend",
            "triggerids": '.json_encode($triggerids).'
        }');
    }

    /**
     * @deprecated use new Item() to get($params)
     */
    public function getItems(Array $hostids, Array $applicationids) {
        $item = new Item($this);
        return $item->get('{
            "output": "extend",
            "hostids": '.json_encode($hostids).',
            "applicationids": '.json_encode($applicationids).',
            "webitems": 1,
            "filter": {
                "status": "0"
            }
        }');
    }

    /**
     * @deprecated use new Item() to get($params)
     */
    public function getItem(Array $itemids) {
        $item = new Item($this);
        return $item->get('{
            "output": ["itemid","hostid","name","key_","value_type","valuemapid","history","trends"],
            "selectHosts": ["name"],
            "itemids": '.json_encode($itemids).',
            "webitems": 1
        }');
    }

    /**
     * @deprecated use new History() to get($params)
     */
    public function getHistory(Array $itemids, int $days = 1, $value_type = 0, $limit = 50) {
        $history = new History($this);
        $dateTo = new \DateTime();
        $dateFrom = new \DateTime();
        $dateFrom->sub(new \DateInterval('P'.$days.'D'));
        return $history->get('{
            "output": "extend",
            "itemids": '.json_encode($itemids).',
            "history": '.$value_type.',
            "sortfield": "clock",
            "sortorder": "DESC",
            "time_from": '.$dateFrom->getTimestamp().',
            "time_till": '.$dateTo->getTimestamp().',
            "limit": '.$limit.'
        }');
    }
}