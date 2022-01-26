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

    public function msgZabbix(String $chamado, Array $eventids) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('event.acknowledge', 
            '{
                "eventids": '.json_encode($eventids).',
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

    public function getHosts(Array $groupids) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('host.get', 
            '{
                "output": ["hostid","name","description"],
                "groupids": '.json_encode($groupids).'
            }');
            return $response;
        }
    }

    public function getApplications(Array $hostids) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('application.get', 
            '{
                "output": ["applicationid","name"],
                "hostids": '.json_encode($hostids).'
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

    public function getEvent(Array $eventids) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('event.get', 
            '{
                "output": "extend",
                "eventids": '.json_encode($eventids).'
            }');
            return $response;
        }
    }

    public function getTrigger(Array $triggerids) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('trigger.get', 
            '{
                "output": "extend",
                "triggerids": '.json_encode($triggerids).'
            }');
            return $response;
        }
    }

    public function getItems(Array $hostids, Array $applicationids) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('item.get', 
            '{
                "output": "extend",
                "hostids": '.json_encode($hostids).',
                "applicationids": '.json_encode($applicationids).',
                "webitems": 1,
                "filter": {
                    "status": "0"
                }
            }');
            return $response;
        }
    }

    public function getItem(Array $itemids) {
        if ($this->token_auth) {
            $response = $this->callEndpoint('item.get', 
            '{
                "output": ["itemid","hostid","name","key_","value_type","valuemapid","history","trends"],
                "selectHosts": ["name"],
                "itemids": '.json_encode($itemids).',
                "webitems": 1
            }');
            return $response;
        }
    }

    public function getChart(Array $itemid, int $width = 1080, int $height = 200, String $from = 'now-1h', String $to = 'now') {

        //NON CONFIGURABLE
        $z_url_index = $this->zabbix_rest_endpoint . "/zabbix/index.php";
        $z_url_graph = $this->zabbix_rest_endpoint . "/zabbix/chart.php";

        // Zabbix 1.8
        // $z_login_data  = "name=" .$z_user ."&password=" .$z_pass ."&enter=Enter";
        // Zabbix 2.0
        $z_login_data = array('name' => $this->zabbix_rest_endpoint_user, 'password' => $this->zabbix_rest_endpoint_pass, 'enter' => "Sign in");

        // file names
        if (!file_exists('zabbix')) {
            mkdir('zabbix', 0777, true);
        }
        $filename_cookie = "zabbix/zabbix_cookie_" . $itemid[0] ."_". date('YmdHis') . ".txt";
        $image_name = "zabbix/zabbix_graph_" . $itemid[0] ."_". date('YmdHis') . ".png";

        //setup curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $z_url_index);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $z_login_data);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $filename_cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $filename_cookie);
        // login
        curl_exec($ch);
        // get graph
        $strItems = '';
        foreach ($itemid as $ind => $item) {
            $strItems .= "&itemids[$ind]=$item";
        }
        $endereco =  $z_url_graph . "?from=$from&to=$to$strItems&type=0&profileIdx=web.item.graph.filter&batch=0&width=$width&height=$height";
        //dd($endereco);
        curl_setopt($ch, CURLOPT_URL, $endereco);
        $output = curl_exec($ch);
        curl_close($ch);
        // delete cookie
        unlink($filename_cookie);

        $fp = fopen($image_name, 'w');
        fwrite($fp, $output);
        fclose($fp);

        return $image_name;
    }
}