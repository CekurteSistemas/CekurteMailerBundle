# Exemplos

Em um controller você poderá utilizar a API do serviço desta forma:

```php
# src/Namespace/YourBundle/Controller/DefaultController.php

// ...

$mail2easy = $this->get('cekurte_mailer.mail2easy');

$contact = $mail2easy->api('contact/search', $filterContactData, 'POST');
// ...

```

O método **api** pode receber três parametros, são eles:

- *(string)* **$uri**: o recurso que será solicitado

- *(array|null)* **$data**: os dados enviados via requisição POST e PUT

- *(string)* **$method**: o método de envio (GET, POST ou PUT)

[Voltar para a Configuração](configuracao.md)