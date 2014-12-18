<?php

namespace Cekurte\MailerBundle\Mailer;

use Cekurte\ComponentBundle\Util\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Classe responsável por permitir que o Symfony2
 * trabalhe com a API da Dinamize realizando a integração
 * com o serviço Mail2Easy.
 *
 * @author João Paulo Cercal <sistemas@cekurte.com>
 * @version 1.0
 */
class Mail2Easy extends ContainerAware
{
    /**
     * URL utilizada no processo de autenticação da API do Mail2Easy
     */
    const SERVICE_AUTH_URL = 'http://api.mail2easy.com.br/authenticate';

    /**
     * @var string
     */
    protected $serviceBaseURL;

    /**
     * @var string
     */
    protected $authToken;

    /**
     * Initialize Mail2Easy
     *
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);

        $serviceHandler = curl_init();

        // Prepara as opções para o processo de autenticação
        curl_setopt($serviceHandler, CURLOPT_URL, self::SERVICE_AUTH_URL);
        curl_setopt($serviceHandler, CURLOPT_HEADER, FALSE);
        curl_setopt($serviceHandler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($serviceHandler, CURLOPT_POST, TRUE);

        $parameter = $this->getContainer()->getParameter('cekurte_mailer');

        // Dados de autenticação. Senhas devem ser passadas sempre como um hash MD5
        $data = array(
            'username' => $parameter['mail2easy']['username'],
            'password' => md5($parameter['mail2easy']['password'])
        );

        curl_setopt($serviceHandler, CURLOPT_POSTFIELDS, http_build_query($data));

        // Captura a resposta da ação de autenticação
        $response = curl_exec($serviceHandler);

        // E o código do status HTTP da resposta
        $code = curl_getinfo($serviceHandler, CURLINFO_HTTP_CODE);

        // Se a requisição HTTP não foi bem sucedida (código 200)
        if ($code != 200) {
            throw new \Exception('Ocorreu um erro ao realizar a autenticação com o serviço de email "Mail2Easy". Verifique se as suas credenciais estão corretas!');
        }

        // Aplica a função da linguagem que converte uma string JSON para um objeto
        $response = json_decode($response);

        // Armazena o Token e a URL que serão usados nas requisições subsequentes
        $this->setAuthToken($response->token);
        $this->setServiceBaseUrl($response->data_service);
    }

    /**
     * Get AuthToken
     *
     * @return string
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set AuthToken
     *
     * @param string $authToken
     *
     * @return Mail2Easy
     */
    protected function setAuthToken($authToken)
    {
        $this->authToken = $authToken;

        return $this;
    }

    /**
     * Get ServiceBaseUrl
     *
     * @return string
     */
    public function getServiceBaseUrl()
    {
        return $this->serviceBaseUrl;
    }

    /**
     * Configura o URL base do serviço
     *
     * @param string $serviceBaseUrl
     *
     * @return Mail2Easy
     */
    protected function setServiceBaseUrl($serviceBaseUrl)
    {
        $this->serviceBaseUrl = $serviceBaseUrl;

        return $this;
    }

    /**
     * Este método é fornecido na API do Mail2Easy::runCommand
     *
     * @param  string $uri      o recurso que será solicitado
     * @param  array|null $data os dados enviados via requisição POST e PUT ou nulo para GET
     * @param  string $method   o método de envio
     *
     * @return array
     */
    public function api($uri, $data = null, $method = 'GET')
    {
        $method = strtoupper($method);

        // Inicializa a biblioteca cURL
        $serviceHandler = curl_init();

        curl_setopt($serviceHandler, CURLOPT_URL, $this->getServiceBaseUrl() . '/' . $uri);
        curl_setopt($serviceHandler, CURLOPT_HEADER, false);

        // Inicializa o(s) cabeçalho(s) HTTP. O cabeçalho "Dinamize-Auth" deve estar sempre presente
        $headers = array('Dinamize-Auth: ' . $this->getAuthToken());

        curl_setopt($serviceHandler, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST') {
            curl_setopt($serviceHandler, CURLOPT_POST, TRUE);
            curl_setopt($serviceHandler, CURLOPT_POSTFIELDS, $data);
        } else if ($method == 'GET') {
            curl_setopt($serviceHandler, CURLOPT_HTTPGET, TRUE);
        } else {
            curl_setopt($serviceHandler, CURLOPT_CUSTOMREQUEST, $method);

            if (strtoupper($method) == 'PUT') {
                // No caso de uma requisição PUT, um cabeçalho HTTP adicional é necessário
                $data = http_build_query($data);
                $headers[] = 'Content-Length: ' . strlen($data);

                curl_setopt($serviceHandler, CURLOPT_POSTFIELDS, $data);
            }
        }

        // Seta os cabeçalhos HTTP
        curl_setopt($serviceHandler, CURLOPT_HTTPHEADER, $headers);

        // Retorna a resposta, já decodificada como objeto PHP.
        return json_decode(curl_exec($serviceHandler));
    }
}
