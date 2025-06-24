<?php

namespace mateusfbi\TotvsRmSoap\Services;

use mateusfbi\TotvsRmSoap\Connection\WebService;

/**
 * Classe FormulaVisual
 *
 * Responsável por executar fórmulas visuais por meio do serviço SOAP da Totvs RM.
 * Essa classe configura os parâmetros necessários (como a fórmula, coligada, contexto e XML de parâmetros),
 * invoca o serviço SOAP e retorna o resultado da execução.
 *
 * @package TotvsRmSoap\Services
 */
class FormulaVisual
{
    private $webService;
    private string $idFormula;
    private string $coligada;
    private string $contexto;
    private string $paramXML;

    /**
     * Construtor da FormulaVisual.
     *
     * Inicializa o cliente SOAP configurado para se comunicar com o endpoint
     * necessário para a execução de fórmulas visuais.
     *
     * @param WebService $webService Instância do serviço web utilizada para obter o cliente SOAP.
     */
    public function __construct(WebService $webService)
    {
        $this->webService = $webService->getClient('/wsFormulaVisual/MEX?wsdl');
    }

    /**
     * Define a fórmula a ser executada.
     *
     * @param string $idFormula Identificador da fórmula.
     * @return void
     */
    public function setFormula(string $idFormula): void
    {
        $this->idFormula = $idFormula;
    }

    /**
     * Define o código da coligada para a execução.
     *
     * @param int $coligada Código da coligada.
     * @return void
     */
     public function setColigada(int $coligada): void
     {
         $this->coligada = $coligada;
     }

    /**
     * Define o contexto que será utilizado na execução da fórmula.
     *
     * Geralmente, o contexto pode incluir informações como usuário, sistema, coligada, etc.
     *
     * @param string $contexto Contexto da execução.
     * @return void
     */
     public function setContexto(string $contexto): void
     {
         $this->contexto = $contexto;
     }

    /**
     * Define os parâmetros da execução em formato XML.
     *
     * @param string $paramXML XML contendo os parâmetros para a execução da fórmula.
     * @return void
     */
    public function setParametersXML(string $paramXML): void
    {
        $this->paramXML = $paramXML;
    }

    /**
     * Executa a fórmula visual via serviço SOAP.
     *
     * Monta a requisição SOAP com os parâmetros configurados e invoca o método Execute do endpoint.
     * Em caso de sucesso, retorna o resultado da execução. Em caso de erro, imprime a mensagem de exceção.
     *
     * @return int Resultado retornado pela execução da fórmula.
     */
    public function execute(): int
    {

        try {

            $execute = $this->webService->Execute([

                'codColigada' => $this->coligada,
                'idFormula' => $this->idFormula,
                'context' => $this->contexto,
                'dataSetXML' => null,
                'parametersXML' => $this->paramXML,
                'ownerData' => null

            ]);

            $return = $execute->ExecuteResult;

        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }
}