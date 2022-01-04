<?php

    namespace Flowti\ZabbixBundle\Service;

    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\HttpClient\HttpClient;

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

        public function login()
        {
            $body          = new \stdClass();
            $body->jsonrpc = '2.0';
            $body->method  = 'user.login';

            $params           = new \stdClass();
            $params->user     = $this->zabbixCredentialsUsername;
            $params->password = $this->zabbixCredentialsPassword;
            $body->params     = $params;

            $body->id   = 1;
            $body->auth = null;

            $jsonEncodedBody = json_encode($body);

            return $this->httpClient->request('POST', $this->zabbixCredentialsHost, ['body' => $jsonEncodedBody]);
        }

        public function getHappyMessage()
        {


            $response = $this->login();
            var_dump($response->getContent());
            exit();
            var_dump($this->container->getParameter('flowti_zabbix.client.host'));
            echo '<br />';
            var_dump($this->container->getParameter('flowti_zabbix.client.username'));
            echo '<br />';
            var_dump($this->container->getParameter('flowti_zabbix.client.password'));
            echo '<br />';

            exit();

            $messages = [
                'You did it! You updated the system! Amazing!',
                'That was one of the coolest updates I\'ve seen all day!',
                'Great work! Keep going!',
            ];

            $index = array_rand($messages);

            return $messages[$index];
        }
    }