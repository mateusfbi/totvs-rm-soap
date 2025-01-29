# TotvsRmSoap

Este projeto é uma implementação em PHP para integração com o serviço SOAP da Totvs RM.

## Requisitos

- PHP 8.0 ou superior
- Extensão SOAP,XML do PHP
- Composer

## Instalação

1. Clone o repositório:
    ```sh
    git clone https://github.com/mateusfbi/totvs-rm-soap.git
    ```
2. Instale as dependências via Composer:
    ```sh
    composer install
    ```

## Configuração

1. Renomeie o arquivo `.env.example` para `.env`:
    ```sh
    mv .env.example .env
    ```
2. Configure as variáveis de ambiente no arquivo `.env` conforme necessário.

## Uso

Para utilizar o serviço SOAP, você pode instanciar a classe `WebService` e chamar os métodos disponíveis. Veja um exemplo básico abaixo:

```php
include_once __DIR__ . '/vendor/autoload.php';

use TotvsRmSoap\Connection\WebService;
use TotvsRmSoap\Services\DataServer;

echo "<pre>";

    $ds =  new  DataServer(new WebService);
    $ds->setDataServer("GlbColigadaDataBR");
    $ds->setContexto("CODSISTEMA=G;CODCOLIGADA=0;CODUSUARIO=mestre");
    $ds->setFiltro("1=1");
    $result = $ds->readView();

    if(array_key_exists('GColigada',$result)){
        $result = $result['GColigada'];
    }else{
        $result = [];
    }

    var_dump($result);

echo "</pre>";

```

## Licença

Este projeto está licenciado sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.