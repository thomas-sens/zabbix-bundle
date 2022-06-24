# ZabbixBundle
Zabbix integration for Symfony

Instalation:
```
composer require flowti/zabbix-bundle 
```

Generate a parameters file:
```
php bin/console config:dump-reference FlowtiZabbixBundle > config/packages/flowti_zabbix.yaml
```

Example: src/config/packages/flowti_zabbix.yaml
```
flowti_zabbix:
    client:
        host: "https://seu-servidor"
        username: "user"
        password: "pass"
```

Call Example:
```
/**
* @Route("/zabbix", name="zabbix-test")
*/
public function zabbixText(FlowtiZabbixClient $zabbix)
{
    dump($zabbix->getTrigger(629272));
    dd($zabbix->getHost('srvdata02rj-q01147'));
}
```
