<?php

    namespace Flowti\ZabbixBundle\Service;

    use Symfony\Component\DependencyInjection\ContainerInterface;
    use GuzzleHttp\Client as GClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FlowtiZabbixClient
    {
        private $zbClient;
        private $zabbix_rest_endpoint_user;
        private $zabbix_rest_endpoint_pass;
        private $token_auth;
        private $zabbix_rest_endpoint;

        public function __construct(ParameterBagInterface $parameter)
        {
            $this->zbClient = new GClient(['verify' => false]);
            $this->token_auth = $this->logIn(); 
            $this->grava_log("Login\n" ,'endpoint-zabbix.log');

            $this->zabbix_rest_endpoint_user = $parameter->get('flowti_zabbix.client.username');
            $this->zabbix_rest_endpoint_pass = $parameter->get('flowti_zabbix.client.password');
            $this->zabbix_rest_endpoint = $parameter->get('flowti_zabbix.client.host');
        }

        public function __destruct()
        {
            $this->grava_log("Logout\n" ,'endpoint-zabbix.log');
            $this->logOut();
        }
    
        private function callEndpoint($method, $params) {
            $authToken = '"id": 0';
            $this->grava_log("$method\n" ,'endpoint-zabbix.log');
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
                $this->grava_log("ERRO: $method ".$response->getStatusCode()."\n" ,'endpoint-zabbix.log');
            }
            if (isset($ret['error'])) {
                $this->grava_log("ERRO $method: ".$ret['error']['message'].' - '.$ret['error']['data']."\n" ,'endpoint-zabbix.log');
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

        function grava_log($msg, $arquivo, $blClean=false){
            //pega o path completo
            $caminho_atual = getcwd();
            //muda o contexto de execução para a pasta logs
            $dir = $caminho_atual."/../var/log";
            if (!file_exists($dir)){
                mkdir($dir, 0700);
            }
            chdir($dir);
            if (!file_exists($arquivo)) {
                $ponteiro = fopen($arquivo, "a+b");
                fclose($ponteiro);
            }
            $data = date("d/m/y");
            $hora = date("H:i:s");
            $ips = $_SERVER['REMOTE_ADDR'];
            $cabecalho = "\n###### INÍCIO [$data $hora] $ips ######\n";
            $rodape = "###### FIM ######\n";
            
            if ($blClean) {
                $file_data = $msg;
            } else {
                $file_data = $cabecalho.$msg.$rodape;
            }
    
            file_put_contents($arquivo, $file_data, FILE_APPEND);
    
            chdir($caminho_atual);
        }
    }