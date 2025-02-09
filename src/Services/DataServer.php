<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Connection\WebService;
use TotvsRmSoap\Utils\Serialize;

/**
 * Classe DataServer
 *
 * Responsável por interagir com o DataServer da Totvs RM. Essa classe permite
 * realizar operações de persistência, leitura e exclusão de registros, além de montar
 * o XML necessário para algumas requisições.
 *
 * @package TotvsRmSoap\Services
 */
class DataServer
{
    private $webService;
    private string $dataServer;
    private string $primaryKey;
    private string $contexto;
    private string $filtro;
    private string $xml;

    /**
     * Construtor do DataServer.
     *
     * Inicializa a instância do DataServer com o cliente SOAP obtido do WebService.
     *
     * @param WebService $webService Instância do serviço web.
     */
    public function __construct(WebService $webService)
    {
        $this->webService = $webService->getClient('/wsDataServer/MEX?wsdl');
    }

    /**
     * Define o DataServer a ser utilizado.
     *
     * @param string $dataServer Nome do DataServer.
     * @return void
     */
    public function setDataServer(string $dataServer): void
    {
        $this->dataServer = $dataServer;
    }

    /**
     * Define a chave primária do registro.
     *
     * @param string $primaryKey Chave primária.
     * @return void
     */
    public function setPrimaryKey(string $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * Define o contexto da requisição.
     *
     * @param string $context Contexto da requisição.
     * @return void
     */
    public function setContexto(string $context): void
    {
        $this->contexto = $context;
    }

    /**
     * Define o filtro para a requisição (ex.: cláusulas WHERE, condições específicas).
     *
     * @param string $filtro Filtro a ser aplicado.
     * @return void
     */
    public function setFiltro(string $filtro): void
    {
        $this->filtro = $filtro;
    }

    /**
     * Monta o XML da requisição do DataServer.
     *
     * Cria um documento XML com o nome da tabela informado e adiciona elementos para cada campo
     * contido no array $data.
     *
     * @param string $table Nome da tabela ou elemento raíz do XML.
     * @param array $data Array associativo contendo os dados (campo => valor).
     * @return void
     */
    public function setXML(string $table, array $data): void
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
     * Persiste um registro no DataServer.
     *
     * Envia a requisição SOAP para salvar um registro, utilizando o XML de dados e o contexto definidos.
     *
     * @return string Resultado da operação de salvamento, conforme retornado pelo serviço SOAP.
     */
    public function saveRecord(): string
    {
        $execute = $this->webService->SaveRecord([
            'DataServerName'    => $this->dataServer,
            'XML'               => $this->xml,
            'Contexto'          => $this->contexto
        ]);

        return $execute->SaveRecordResult;
    }

    /**
     * Lê um registro específico do DataServer, utilizando a chave primária definida.
     *
     * @return array Array associativo contendo os dados do registro lido.
     */
    public function readRecord(): array
    {
        try {

            $execute = $this->webService->ReadRecord([
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
     * Lê dados do DataServer utilizando um filtro específico.
     *
     * Envia uma requisição SOAP com o nome do DataServer, filtro e contexto para retornar
     * os dados que satisfazem as condições definidas.
     *
     * @return array Array associativo contendo os dados retornados.
     */
    public function readView(): array
    {

        try {

            $execute = $this->webService->ReadView([
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
     * Exclui um registro do DataServer utilizando um XML de dados.
     *
     * Envia uma requisição SOAP para remover um registro, com base no XML fornecido e no contexto.
     *
     * @return int Resultado da operação de exclusão conforme retornado pelo serviço SOAP.
     */
    public function deleteRecord(): int
    {
        try {

            $execute = $this->webService->DeleteRecord([
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
     * Exclui um registro do DataServer utilizando a chave primária.
     *
     * Envia a requisição SOAP para remover o registro identificado pela chave primária.
     *
     * @return array Array associativo com o resultado da operação de exclusão.
     */
    public function deleteRecordByKey(): array
    {
        try {

            $execute = $this->webService->DeleteRecordByKey([
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
