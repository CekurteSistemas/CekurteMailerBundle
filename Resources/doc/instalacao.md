# Instalação

Assumimos que você já tenha o binário do composer instalado ou o arquivo composer.phar, sendo assim, execute o seguinte comando:

```bash
$ composer require cekurte/mailerbundle
```

Agora adicione o Bundle no seu Kernel:

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Cekurte\MailerBundle\CekurteMailerBundle(),
        // ...
    );
}
```

[Voltar para o Index](index.md) - [Ver a Configuração](configuracao.md)