<?php

namespace Flowti\ZabbixBundle\Service;

use GuzzleHttp\Client as GClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FlowtiZabbixClient
{
    private $zbClient;
    private $zabbix_rest_endpoint_user;
    private $zabbix_rest_endpoint_pass;
    private $token_auth;
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

    public function __destruct()
    {
        $this->logOut();
    }

    private function callEndpoint($method, $params) {
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

        $response = $this->zbClient->request('POST', $this->zabbix_rest_endpoint, $input);

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

    public function msgZabbix($chamado, $event_id) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('event.acknowledge', 
            '{
                "eventids": "'.$event_id.'",
                "action": "4",
                "message": "Qualitor: '.$chamado.'"
            }');
            return $response;
        }
    }

    public function getHost(String $hostname) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('host.get', 
            '{
                "output": ["hostid","description"],
                "filter": {
                    "host": [
                        "'.$hostname.'"
                    ]
                },
                "selectInventory": ["os"]
            }');
            return $response;
        }
    }

    public function getHostGroups() {
        if ($this->token_auth) {
            $response = $this->callEndpoint('hostgroup.get', 
            '{
                "output": ["groupid","name"],
                "real_hosts": 1
            }');

            return $response;
        }
    }

    public function getEvent(String $eventid) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('event.get', 
            '{
                "output": "extend",
                "eventids": "'.$eventid.'"
            }');
            return $response;
        }
    }

    public function getTrigger(String $triggerid) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('trigger.get', 
            '{
                "output": "extend",
                "triggerids": "'.$triggerid.'"
            }');
            return $response;
        }
    }
}