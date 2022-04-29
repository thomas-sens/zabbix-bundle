<?php

namespace Flowti\ZabbixBundle\Model;

use Flowti\ZabbixBundle\Service\FlowtiZabbixClient;

class Chart
{
    private $zabbixClient;

    public function __construct(FlowtiZabbixClient $zabbixClient)
    {
        $this->zabbixClient = $zabbixClient;
    }

    public function get(Array $itemid, int $width = 1080, int $height = 200, String $from = 'now-1h', String $to = 'now') {

        //NON CONFIGURABLE
        $z_url_index = $this->zabbixClient->getZabbixRestEnpoint() . "/zabbix/index.php";
        $z_url_graph = $this->zabbixClient->getZabbixRestEnpoint() . "/zabbix/chart.php";

        // Zabbix 1.8
        // $z_login_data  = "name=" .$z_user ."&password=" .$z_pass ."&enter=Enter";
        // Zabbix 2.0
        $z_login_data = array('name' => $this->zabbixClient->getZabbixRestEnpointUser(), 'password' => $this->zabbixClient->getZabbixRestEnpointPass(), 'enter' => "Sign in");

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