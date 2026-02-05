<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Services\AbstractService;

use TotvsRmSoap\Connection\WebService;
use TotvsRmSoap\Utils\Serialize;

/**
 * Classe ConsultaSQL
 *
 * Responsável por realizar consultas SQL através do serviço SOAP da Totvs RM.
 * Essa classe prepara os parâmetros da consulta e invoca o serviço SOAP específico
 * para execução da sentença SQL, retornando o resultado processado.
 *
 * @package TotvsRmSoap\Services
 */

class ConsultaSQL extends AbstractService
{
    private string $codSentenca;
    private string $coligada;
    private string $sistema;
    private string $parametros;

    /**
     * Construtor da ConsultaSQL.
     *
     * Inicializa a instância da classe com a configuração do cliente SOAP
     * para o endpoint de consulta SQL.
     *
     * @param WebService $webService Instância do serviço web utilizada para conectar ao endpoint SOAP.
     */

    public function __construct(WebService $webService)
    {
        $this->webService = $webService->getClient('/wsConsultaSQL/MEX?wsdl');
    }

    /**
     * Define a sentença SQL para a consulta.
     *
     * @param string $codSentenca Código da Sentença SQL a ser utilizada.
     * @return void
     */
    public function setCodSentenca(string $codSentenca): void
    {
        $this->codSentenca = $codSentenca;
    }

    /**
     * Define o código da coligada para a consulta.
     *
     * @param int $coligada Código da coligada onde a consulta será executada.
     * @return void
     */
    public function setColigada(int $coligada): void
    {
        $this->coligada = $coligada;
    }

    /**
     * Define o código do sistema para a consulta.
     *
     * @param string $sistema Código do sistema associado à consulta.
     * @return void
     */
    public function setSistema(string $sistema): void
    {
        $this->sistema = $sistema;
    }

    /**
     * Configura os parâmetros adicionais da consulta.
     *
     * Recebe um array associativo onde cada par chave-valor é convertido em uma string
     * no formato "chave=valor". Todos os parâmetros são então unidos por ponto e vírgula.
     *
     * @param array $params Array associativo com os parâmetros adicionais.
     * @return void
     */
    public function setParametros(array $params = []): void
    {
        $array = [];

        foreach ($params as $key => $value) {
            $array[] = "{$key}={$value}";
        }

        $this->parametros = implode(';', $array);
    }

    

    /**
     * Realiza a consulta SQL através do serviço SOAP.
     *
     * Este método monta os parâmetros da consulta utilizando os dados definidos pelas
     * configurações anteriores (sentença, coligada, sistema e parâmetros), invoca o método
     * RealizarConsultaSQL do serviço SOAP e processa o retorno utilizando a classe Serialize.
     *
     * Em caso de erro durante a consulta, uma mensagem será exibida.
     *
     * @return array Array associativo contendo a resposta da consulta. Se a consulta tiver sucesso,
     *               retorna o resultado encontrado na chave 'Resultado'. Caso contrário, retorna false.
     */
    public function RealizarConsultaSQL(): array
    {
        $params = [
            'codSentenca' => $this->codSentenca,
            'codColigada' => $this->coligada,
            'codSistema' => $this->sistema,
            'parameters' => empty($this->parametros) ? null : $this->parametros,
        ];
        $result = $this->callWebServiceMethod('RealizarConsultaSQL', $params, null);
        return ['response' => Serialize::result($result)['Resultado'] ?? false];
    }

}
