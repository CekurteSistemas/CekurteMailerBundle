<?php

namespace Cekurte\MailerBundle\Mailer;

/**
 * Classe responsável por permitir que o Symfony2
 * trabalhe com a API da Dinamize realizando a integração
 * com o serviço Mail2Easy.
 *
 * @author João Paulo Cercal <sistemas@cekurte.com>
 * @version 1.0
 */
class Mail2Easy
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
     * @var array
     */
    protected $template;

    /**
     * @var string
     */
    protected $campanha;

    /**
     * @var string
     */
    protected $remetente;

    /**
     * @var array
     */
    protected $grupo;

    /**
     * @var array
     */
    protected $contato;

    /**
     * @var array
     */
    protected $agendamento;

    /**
     * Inicialização
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $serviceHandler = curl_init();

        // Prepara as opções para o processo de autenticação
        curl_setopt($serviceHandler, CURLOPT_URL, self::SERVICE_AUTH_URL);
        curl_setopt($serviceHandler, CURLOPT_HEADER, FALSE);
        curl_setopt($serviceHandler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($serviceHandler, CURLOPT_POST, TRUE);

        // Dados de autenticação. Senhas devem ser passadas sempre como um hash MD5
        $data = array('username' => $username, 'password' => md5($password));

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
    public function setAuthToken($authToken)
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
    public function setServiceBaseUrl($serviceBaseUrl)
    {
        $this->serviceBaseUrl = $serviceBaseUrl;

        return $this;
    }

    /**
     * Recupera o template
     *
     * @return array
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Configura o template
     *
     * @param array $template um array contendo os seguintes argumentos:
     * name       string Nome do template
     * subject    string Assunto do template
     * body_html  string Corpo do template em formato HTML
     * body_text  string Corpo do template em formato texto
     * type       string Tipo de template: HTML e Texto(HT), (H)TML, (T)exto
     * model      string Define se o template deve ser salvo como um modelo (1) ou não (0)
     *
     * @return Mail2Easy
     */
    public function setTemplate(array $template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Recupera o nome da campanha
     *
     * @return string
     */
    public function getCampanha()
    {
        return $this->campanha;
    }

    /**
     * Configura o nome da campanha
     *
     * @param string $campanha
     *
     * @return Mail2Easy
     */
    public function setCampanha($campanha)
    {
        $this->campanha = $campanha;

        return $this;
    }

    /**
     * Recupera o e-mail do remetente
     *
     * @return string
     */
    public function getRemetente()
    {
        return $this->remetente;
    }

    /**
     * Configura o e-mail do remetente
     *
     * @param string $remetente e-mail do remetente
     *
     * @return Mail2Easy
     */
    public function setRemetente($remetente)
    {
        $this->remetente = $remetente;

        return $this;
    }

    /**
     * Recupera o(s) nome(s) do(s) grupo(s)
     *
     * @return array
     */
    public function getGrupos()
    {
        return $this->grupo;
    }

    /**
     * Verifica se um grupo já foi adicionado
     *
     * @return bool
     */
    public function hasGroup($grupo)
    {
        $groups = $this->getGrupos();

        if (!empty($groups)) {
            foreach ($groups as $group) {
                if ($group === $grupo) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Configura os grupos
     *
     * @param array $grupo
     *
     * @return Mail2Easy
     */
    public function setGrupo(array $grupo)
    {
        $this->grupo = $grupo;

        return $this;
    }

    /**
     * Adiciona grupo(s)
     *
     * @param string|array $grupo
     *
     * @return Mail2Easy
     */
    public function addGrupo($grupo)
    {
        if (is_string($grupo)) {

            $found = $this->hasGroup($grupo);

            if ($found === false) {
                $this->grupo[] = $grupo;
            }

        } else {
            $this->setGrupo($grupo);
        }

        return $this;
    }

    /**
     * Recupera a quantidade total de grupos
     *
     * @return int
     */
    public function getTotalGrupo()
    {
        return is_string($this->grupo) ? 1 : count($this->grupo);
    }

    /**
     * Recupera o(s) contato(s)
     *
     * @return array
     */
    public function getContatos()
    {
        return $this->contato;
    }

    /**
     * Adiciona um contato que irá receber o(s) e-mail(s)
     *
     * @param string $nome
     * @param string $email
     * @param string|array $grupo
     *
     * @return Mail2Easy
     */
    public function addContato($nome, $email, $grupo)
    {
        $this->contato[] = array(
            'nome'  => $nome,
            'email' => $email,
            'grupo' => $grupo,
        );

        $this->addGrupo($grupo);

        return $this;
    }

    /**
     * Recupera a quantidade total de contatos
     *
     * @return int
     */
    public function getTotalContato()
    {
        return count($this->contato);
    }

    /**
     * Recupera os dados de agendamento
     *
     * @return array
     */
    public function getAgendamento()
    {
        return $this->agendamento;
    }

    /**
     * Configura os dados de agendamento
     *
     * @param array $agendamento um array contendo os seguintes argumentos:
     * name      string     Nome do agendamento
     * reply_to  string     Endereço de resposta do agendamento
     * from      string     Nome do remetente do agendamento
     * scheduled \Datetime  Data e hora da criação do agendamento
     *
     * @return Mail2Easy
     */
    public function setAgendamento(array $agendamento)
    {
        $this->agendamento = $agendamento;

        return $this;
    }













    protected function runCommand($command, $data = null, $method = 'GET')
    {
        $method = strtoupper($method);

        // Inicializa a biblioteca cURL
        $serviceHandler = curl_init();

        curl_setopt($serviceHandler, CURLOPT_URL, $this->getServiceBaseUrl() . '/' . $command);
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

    protected function hasResourceByFilterList($resource, $data)
    {
        $response = $this->runCommand($resource . '/search', $data, 'POST');

        if ($response->total === 0) {

           return false;

        } elseif ($response->total === 1) {

            return $response->data_list[0];

        }

        throw new \Exception(sprintf('A busca pelo recurso "%s" retornou mais de um registro. Refine a sua busca!', $resource));
    }

    protected function hasResourceByName($resource, $value, $name = 'query', $operator = '=')
    {
        $data = array(
            'filter_list'   => json_encode(array(array(
                'name'      => $name,
                'operator'  => $operator,
                'value'     => $value,
            )))
        );

        return $this->hasResourceByFilterList($resource, $data);
    }

    protected function getTemplateId()
    {
        $template = $this->getTemplate();

        $resource = $this->hasResourceByName('template', $template['name']);

        if ($resource === false) {
            $resource = $this->runCommand('template/create', $template, 'POST');
        }

        if (!isset($resource->id)) {
            throw new \Exception($resource->message);
        }

        return $resource->id;
    }

    protected function getCampanhaId()
    {
        $campanha = $this->getCampanha();

        $resource = $this->hasResourceByName('campaign', $campanha);

        if ($resource === false) {
            $resource = $this->runCommand('campaign/create', array('name' => $campanha), 'POST');
        }

        if (!isset($resource->id)) {
            throw new \Exception($resource->message);
        }

        return $resource->id;
    }

    protected function getRemetenteId()
    {
        $remetente = $this->getRemetente();

        $resource = $this->hasResourceByName('mail/sender', $remetente, 'email');

        if ($resource === false) {

            $resource = $this->runCommand('mail/sender/create', array('email' => $remetente), 'POST');

            if (!empty($resource->id)) {
                $this->runCommand(sprintf('mail/sender/%s/activate', $resource->id));
            }
        }

        if (!isset($resource->id)) {
            throw new \Exception($resource->message);
        }

        return $resource->id;
    }

    protected function getGrupoId($grupo)
    {
        $resource = $this->hasResourceByName('group', $grupo);

        if ($resource === false) {
            $resource = $this->runCommand('group/create', array('name' => $grupo), 'POST');
        }

        if (!isset($resource->id)) {
            throw new \Exception($resource->message);
        }

        return $resource->id;
    }

    public function sendEmailMessage()
    {
        $templateId     = $this->getTemplateId();

        $campanhaId     = $this->getCampanhaId();

        $remetenteId    = $this->getRemetenteId();

        $agendamento    = $this->getAgendamento();

        $grupos         = $this->getGrupos();
        $contatos       = $this->getContatos();

        if (count($grupos) === 0) {
            throw new \Exception('Você deve configurar um grupo de e-mails para realizar o agendamento!');
        }

        if (count($contatos) === 0) {
            throw new \Exception('Você deve configurar ao menos um contato para realizar o agendamento!');
        }

        // ------------------------------------------------------------------
        // Agendamento

        $data = array(
            'camp_id'               => $campanhaId,                 // ID da campanha
            'temp_id'               => $templateId,                 // ID do template
            'send_id'               => $remetenteId,                // ID do remetente
            'sche_name'             => $agendamento['name'],        // Nome do agendamento
            'sche_reply_to_email'   => $agendamento['reply_to'],    // Endereço de resposta do agendamento
            'sche_from'             => $agendamento['from']         // Nome do remetente do agendamento
        );

        $schedule = $this->runCommand('mail/schedule/create', $data, 'POST');

        // ------------------------------------------------------------------
        // Grupos

        $groupIds = array();

        foreach ($grupos as $grupo) {
            $groupIds[] = $this->getGrupoId($grupo);
        }

        // Configura os grupos que serão adicionados ao agendamento
        $data = array('groups' => json_encode($groupIds));

        // Executa a chamada de adição do grupo
        $group = $this->runCommand('mail/schedule/' . $schedule->id . '/recipient/group/add', $data, 'POST');

        if ($group->affected_rows !== 1) {
            throw new \Exception('Ocorreu um problema ao adicionar um grupo de e-mails ao agendamento!');
        }

        // ------------------------------------------------------------------
        // Contatos

        foreach ($contatos as $contato) {

            $contact = $this->hasResourceByFilterList('contact', array(
                'filter_list'   => json_encode(array(
                    array(
                        'name'      => 'email',
                        'operator'  => '=',
                        'value'     => $contato['email'],
                    ),
                    array(
                        'name'      => 'nome',
                        'operator'  => '=',
                        'value'     => $contato['nome'],
                    ),
                ))
            ));

            if ($contact === false) {

                $userGroupIds = array();

                if (is_string($contato['grupo'])) {
                    $userGroupIds[] = $this->getGrupoId($contato['grupo']);
                } else {
                    foreach ($contato['grupo'] as $grupo) {
                        $userGroupIds[] = $this->getGrupoId($grupo);
                    }
                }

                // Configura os grupos que serão adicionados ao agendamento
                $data = array(
                    'status_email'  => 'OPTIN',
                    'group_list'    => json_encode($userGroupIds),
                    'fields'        => json_encode(array(
                        'nome'      => $contato['nome'],
                        'email'     => $contato['email'],
                    ))
                );

                // Executa a chamada de adição de contato
                $contact = $this->runCommand('contact/create', $data, 'POST');
            }

            if (!isset($contact->id)) {
                throw new \Exception($contact->message);
            }
        }

        // ------------------------------------------------------------------
        // Agendamento

        // Quarto passo para o envio: Obter o total de contatos, considerando-se todos os grupos adicionados
        $view = $this->runCommand('mail/schedule/' . $schedule->id . '/recipient/view');

        // Último passo para o envio: Configurar o agendamento
        // Exemplo de dados necessários para a configuração do agendamento:
        $data = array(
            'sche_dt_scheduled' => $agendamento['scheduled']->format('Y-m-d H:i:s'),
            'sche_status'       => 'AM',
            'sche_total'        => $view->total,
        );

        // Executa a chamada de configuração do agendamento, usando o método PUT
        $send = $this->runCommand('mail/schedule/' . $schedule->id, $data, 'PUT');

        return $send->affected_rows === 1 ? true : false;
    }
}
