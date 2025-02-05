<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Connection\WebService;
use TotvsRmSoap\Utils\Serialize;

class ConsultaSQL
{
    private string $sentenca;
    private string $coligada;
    private string $sistema;
    private string $parametros;
    private $connection;

    public function __construct(WebService $ws)
    {
        $this->connection = $ws->getClient('/wsConsultaSQL/MEX?wsdl');
    }

    /**
     * @param string $sentenca
     * @return void
     */

    public function setSentenca(string $sentenca): void
    {
        $this->sentenca = $sentenca;
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
     * @param string $sistema
     * @return void
     */

    public function setSistema(string $sistema): void
    {
        $this->sistema = $sistema;
    }

    /**
     * @param array $params
     * @return void
     */

    public function setParametros(array $params = []): void
    {
        $array = [];

        if ($params):

            foreach ($params as $key => $value):

                $array[] = "{$key}={$value}";
            endforeach;
        endif;

        $this->parametros = join(';', $array);
    }

    /**
     * @return array
     */

    public function RealizarConsultaSQL(): array
    {
        try {

            $execute = $this->connection->RealizarConsultaSQL([
                'codSentenca' => $this->sentenca,
                'codColigada' => $this->coligada,
                'codSistema' => $this->sistema,
                'parameters' => empty($this->parametros)?null:$this->parametros,
            ]);

            $result = Serialize::result($execute->RealizarConsultaSQLResult);

        } catch (\Exception $e) {
            echo '<br /><br /> ' . $e->getMessage() . PHP_EOL;
        }

        return ['response' => (isset($result['Resultado']) ? $result['Resultado'] : false)];
    }
}
