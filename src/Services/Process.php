<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Services\AbstractService;

use TotvsRmSoap\Connection\WebService;


/**
 * Classe Process
 *
 * Responsável por gerenciar a execução de processos via serviço SOAP no projeto TotvsRmSoap.
 *
 * A classe prepara os parâmetros da requisição, invoca o serviço SOAP através do WebService e
 * trata a resposta utilizando a utilidade de serialização. Ela é projetada para centralizar
 * toda lógica de processamento, facilitando a manutenção e extensibilidade do código.
 *
 * @package TotvsRmSoap\Services
 */
class Process extends AbstractService
{
    private string $process;
    private string $xml;
    private string $jobId;
    private string $execId;

    /**
     * Construtor do Process.
     *
     * Inicializa a instância de Process com o cliente SOAP configurado
     * para se comunicar com o endpoint do serviço.
     *
     * @param WebService $webService Instância do serviço web.
     */
    public function __construct(WebService $webService)
    {
        $this->webService = $webService->getClient('/wsProcess/MEX?wsdl');
    }

    /**
     * Define o nome do processo que será executado.
     *
     * @param string $process Nome do processo a ser chamado no serviço SOAP.
     * @return void
     */
    public function setProcess(string $process): void
    {
        $this->process = $process;
    }

    /**
     * Define o XML com os parâmetros da requisição.
     *
     * @param string $xml XML contendo os parâmetros do processo.
     * @return void
     */
    public function setXML(string $xml): void
    {
        $this->xml = $xml;
    }

    /**
     * Define o identificador do job do processo.
     *
     * @param string $jobId Identificador único do job.
     * @return void
     */
    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    /**
     * Define o identificador da execução do processo.
     *
     * @param string $execId Identificador da execução.
     * @return void
     */
    public function setExecId(string $execId): void
    {
        $this->execId = $execId;
    }

    

    /**
     * Executa o processo utilizando os parâmetros em formato XML.
     *
     * Prepara a requisição SOAP com o nome do processo e o XML de parâmetros configurado,
     * envia a requisição e retorna o resultado da execução.
     *
     * @return int Resultado retornado pelo serviço SOAP para a execução com XML.
     */
    public function executeWithXmlParams(): int
    {
        $params = [
            'ProcessServerName' => $this->process,
            'strXmlParams'      => $this->xml
        ];
        return (int) $this->callWebServiceMethod('ExecuteWithXmlParams', $params, 0);
    }

    /**
     * Executa o processo utilizando os parâmetros em formato padrão.
     *
     * Prepara a requisição SOAP com o nome do processo e o XML de parâmetros configurado,
     * envia a requisição e retorna o resultado da execução.
     *
     * @return int Resultado retornado pelo serviço SOAP para a execução com parâmetros padrão.
     */
    public function ExecuteWithParams(): int
    {
        $params = [
            'ProcessServerName' => $this->process,
            'strXmlParams'      => $this->xml
        ];
        return (int) $this->callWebServiceMethod('ExecuteWithParams', $params, 0);
    }

    /**
     * Obtém o status do processo.
     *
     * Envia uma requisição SOAP contendo o jobId e execId configurados e retorna o status atual do processo.
     *
     * @return int Status atual do processo, conforme retornado pelo serviço SOAP.
     */
    public function getProcessStatus(): int
    {
        $params = [
            'jobId'  => $this->jobId,
            'execId' => $this->execId
        ];
        return (int) $this->callWebServiceMethod('getProcessStatus', $params, 0);
    }
}
