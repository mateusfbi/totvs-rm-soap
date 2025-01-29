<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Connection\WebService;

class FormulaVisual
{
    private string $idFormula;
    private string $coligada;
    private string $contexto;
    private string $paramXML;
    private $connection;

    public function __construct(WebService $ws)
    {
        $this->connection = $ws->getClient('/wsFormulaVisual/MEX?wsdl');
    }

    /**
     * @param string $idFormula
     * @return void
     */

    public function setFormula(string $idFormula): void
    {
        $this->idFormula = $idFormula;
    }

    /**
     * @param int $coligada
     * @return void
     */

     public function setColigada(int $coligada): void
     {
         $this->coligada = $coligada;
     }

    /**
     * @param string $contexto
     * @return void
     */

     public function setContexto(string $contexto): void
     {
         $this->contexto = $contexto;
     }
 

    /**
     * @param string $xml
     * @return void
     */

    public function setParametersXML(string $paramXML): void
    {
        $this->paramXML = $paramXML;
    }

    /**
     * @return int
     */

    public function execute(): int
    {

        try {

            $execute = $this->connection->Execute([

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