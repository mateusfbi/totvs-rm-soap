<?php

namespace TotvsRmSoap\Connection;

use \SoapClient;
use Dotenv\Dotenv;

/**
 * Classe WebService
 *
 * Responsável por configurar e retornar uma instância do cliente SOAP para comunicação com
 * os serviços da Totvs RM. Essa classe realiza o carregamento das variáveis de ambiente a partir
 * de um arquivo .env e utiliza essas informações para configurar a conexão SOAP.
 *
 * É utilizada a biblioteca Dotenv para carregar as variáveis de ambiente a partir do diretório
 * base do projeto, permitindo a configuração dinâmica da URL, usuário e senha do serviço.
 *
 * @package TotvsRmSoap\Connection
 */
class WebService
{
    /**
     * Construtor da classe WebService.
     *
     * Carrega as variáveis de ambiente utilizando a biblioteca Dotenv a partir do diretório base
     * do projeto. Essas variáveis são utilizadas para configurar a conexão SOAP.
     */
    public function __construct()
    {

        $dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
        $dotenv->load();
    }

    /**
     * Retorna uma instância do SoapClient configurada para o endpoint especificado.
     *
     * Este método constrói a URL do serviço concatenando a variável de ambiente WS_URL com o
     * caminho ($path) fornecido. Em seguida, cria e retorna uma instância do SoapClient
     * com as seguintes opções:
     * - 'login' e 'password': obtidos das variáveis de ambiente WS_USER e WS_PASS.
     * - Autenticação básica.
     * - Uso da versão SOAP 1.1.
     * - Ativação do trace para debugar a requisição.
     * - Configuração do stream context para lidar com conexões SSL sem verificação de peer.
     *
     * Em caso de erro na conexão, o método exibe uma mensagem de erro e encerra a execução.
     *
     * @param string $path Caminho relativo do serviço SOAP (parte do WSDL) a ser utilizado.
     * @return SoapClient Instância do cliente SOAP configurada para realizar as requisições.
     */
    public function getClient(string $path): SoapClient
    {
        try {

            $connection = new SoapClient($_ENV['WS_URL'] . $path, [
                'login'                 => $_ENV['WS_USER'],
                'password'              => $_ENV['WS_PASS'],
                'authentication'        => SOAP_AUTHENTICATION_BASIC,
                'soap_version'          => SOAP_1_1,
                'trace'                 => 1,
                'excepitions'           => 0,
                "stream_context" => stream_context_create(
                    [
                        'ssl' => [
                            'verify_peer'       => false,
                            'verify_peer_name'  => false,
                            'allow_self_signed' => true
                        ]
                    ]
                )
            ]);
        } catch (\Exception $e) {
            echo '<h2 style="color:red;"><br /><br /> Erro: Não foi possival conectar ao servidor do RM.' . ' - ' . getenv('WS_URL') . '<br /></h2>' . $e->getMessage() . PHP_EOL;
            exit;
        }

        return $connection;
    }
}
