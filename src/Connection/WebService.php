<?php

namespace mateusfbi\TotvsRmSoap\Connection;

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
    private static bool $_envLoaded = false;

    public function __construct()
    {
        if (!self::$_envLoaded) {
            $dotenv = Dotenv::createImmutable(__DIR__ . "/../../");
            $dotenv->load();
            self::$_envLoaded = true;
        }
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
        $url = $_ENV['WS_URL'] . $path;
        $options = [
            'login'                 => $_ENV['WS_USER'],
            'password'              => $_ENV['WS_PASS'],
            'authentication'        => SOAP_AUTHENTICATION_BASIC,
            'soap_version'          => SOAP_1_1,
            'trace'                 => 1,
            'exceptions'            => 1, // Corrigido de 'excepitions' para 'exceptions' e definido como true
            "stream_context" => stream_context_create(
                [
                    'ssl' => [
                        // ATENÇÃO: Em produção, é ALTAMENTE recomendado definir estas opções como true
                        // e configurar corretamente os certificados CA para garantir a segurança SSL.
                        // Para desenvolvimento, podem ser definidos como false no .env.
                        'verify_peer'       => filter_var($_ENV['WS_SSL_VERIFY_PEER'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'verify_peer_name'  => filter_var($_ENV['WS_SSL_VERIFY_PEER_NAME'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'allow_self_signed' => filter_var($_ENV['WS_SSL_ALLOW_SELF_SIGNED'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    ]
                ]
            )
        ];
        return $this->createSoapClient($url, $options);
    }

    /**
     * Método auxiliar para criar uma instância do SoapClient e tratar exceções.
     *
     * @param string $url URL completa do serviço SOAP.
     * @param array $options Opções para o SoapClient.
     * @return \SoapClient Instância do cliente SOAP.
     * @throws \RuntimeException Se houver um erro na conexão com o servidor SOAP.
     */
    private function createSoapClient(string $url, array $options): \SoapClient
    {
        try {
            return new \SoapClient($url, $options);
        } catch (\Exception $e) {
            $errorMessage = 'Erro: Não foi possível conectar ao servidor do RM. URL: ' . $url . ' - ' . $e->getMessage();
            error_log($errorMessage);
            throw new \RuntimeException($errorMessage, 0, $e);
        }
    }
}
