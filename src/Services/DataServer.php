<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Connection\WebService;
use TotvsRmSoap\Utils\Serialize;

class DataServer
{
    private string $dataServer;
    private string $primaryKey;
    private string $contexto;
    private string $filtro;
    private string $xml;
    private $connection;

    public function __construct(WebService $ws)
    {
        $this->connection = $ws->getClient('/wsDataServer/MEX?wsdl');
    }

    /**
     * @param string $dataServer
     * @return void
     */

    public function setDataServer(string $dataServer): void
    {
        $this->dataServer = $dataServer;
    }

    /**
     * @param string $primaryKey
     * @return void
     */

    public function setPrimaryKey(string $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
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
     * @param string $filtro
     * @return void
     */

    public function setFiltro(string $filtro): void
    {
        $this->filtro = $filtro;
    }

    /**
     * @param string $table
     * @param array $data
     * @return void
     */

    public function setXML(string $table, array $data) : void
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $element = $dom->createElement($table);
        $dom->appendChild($element);

        foreach ($data as $key => $value) :
            $append = $dom->createElement($key, $value);
            $element->appendChild($append);
        endforeach;

        $this->xml = $dom->saveXML();
    }

    /**
     * @return string
     */

    public function saveRecord(): string
    {
        $execute = $this->connection->SaveRecord([
            'DataServerName'    => $this->dataServer,
            'XML'               => $this->xml,
            'Contexto'          => $this->contexto
        ]);

        return $execute->SaveRecordResult;

    }

    /**
     * @return array
     */

    public function readRecord(): array
    {
        try {

            $execute = $this->connection->ReadRecord([
                'DataServerName'    => $this->dataServer,
                'PrimaryKey'        => $this->primaryKey,
                'Contexto'          => $this->contexto
            ]);

            $result = Serialize::result($execute->ReadRecordResult);

        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $result;
    }

    /**
     * @return array
     */

     public function readView(): array
     {

         try {

             $execute = $this->connection->ReadView([
                 'DataServerName'    => $this->dataServer,
                 'Filtro'            => $this->filtro,
                 'Contexto'          => $this->contexto
             ]);

             $result = Serialize::result($execute->ReadViewResult);

         } catch (\Exception $e) {
             echo '<br /><br /> ' . $e->getMessage() . PHP_EOL;
         }

         return $result;
     }

    /**
     * @return int
     */

    public function deleteRecord(): int
    {
        try {

            $execute = $this->connection->DeleteRecord([
                'DataServerName'    => $this->dataServer,
                'XML'               => $this->xml,
                'Contexto'          => $this->contexto
            ]);

            $result = $execute->DeleteRecordResult;

        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $result;
    }

    /**
     * @return array
     */

    public function deleteRecordByKey(): array
    {
        try {

            $execute = $this->connection->DeleteRecordByKey([
                'DataServerName'    => $this->dataServer,
                'PrimaryKey'        => $this->primaryKey,
                'Contexto'          => $this->contexto,
            ]);

            $result = Serialize::result($execute->DeleteRecordByKeyResult);

        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $result;
    }

}
