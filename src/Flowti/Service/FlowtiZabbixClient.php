<?php

    namespace Flowti\ZabbixBundle\Service;

    use Symfony\Component\DependencyInjection\ContainerInterface;
    use GuzzleHttp\Client as GClient;

    class FlowtiZabbixClient
    {
        protected $httpClient;
        protected $zabbixCredentialsHost;
        protected $zabbixCredentialsUsername;
        protected $zabbixCredentialsPassword;

        public function __construct(ContainerInterface $container)
        {
            $this->container  = $container;
            $this->httpClient = HttpClient::create([
                'headers' => [
                    'Content-Type' => 'application/json-rpc',
                ]
            ]);

            $this->zabbixCredentialsHost     = $this->container->getParameter('flowti_zabbix.client.host');
            $this->zabbixCredentialsUsername = $this->container->getParameter('flowti_zabbix.client.username');
            $this->zabbixCredentialsPassword = $this->container->getParameter('flowti_zabbix.client.password');
        }

        public function __destruct()
        {
            $this->funcoes->grava_log("Logout\n" ,'endpoint-zabbix.log');
            $this->logOut();
        }
    
        private function callEndpoint($method, $params) {
            $authToken = '"id": 0';
            $this->funcoes->grava_log("$method\n" ,'endpoint-zabbix.log');
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
                $this->funcoes->grava_log("ERRO: $method ".$response->getStatusCode()."\n" ,'endpoint-zabbix.log');
            }
            if (isset($ret['error'])) {
                $this->funcoes->grava_log("ERRO $method: ".$ret['error']['message'].' - '.$ret['error']['data']."\n" ,'endpoint-zabbix.log');
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