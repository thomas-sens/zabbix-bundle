# ZabbixBundle
Zabbix integration for Symfony

Para gerar o arquivo de parâmetros:
```
php bin/console config:dump-reference FlowtiZabbixBundle > config/packages/flowti_zabbix.yaml
```

Configuração dos parâmetros, exemplo: (src/config/packages/flowti_zabbix.yaml)
```
flowti_zabbix:
    client:
        host: "https://seu-servidor/api_jsonrpc.php"
        username: "user"
        password: "pass"
```

Exemplo de chamada:
```
/**
* @Route("/zabbix", name="teste-zabbix")
*/
public function testeZabbix(FlowtiZabbixClient $zabbix)
{
    dump($zabbix->getTrigger(629272));
    dd($zabbix->getHost('srvdata02rj-q01147'));
}
```