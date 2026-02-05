<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Services\AbstractService;

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
class DataServer extends AbstractService
{
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
     * Define diretamente o XML da requisição do DataServer.
     *
     * @param string $xml Conteúdo XML completo.
     * @return string XML definido.
     */
    public function setXML(string $xml): string
    {
        return $this->xml = $xml;
    }

    /**
     * Monta o XML da requisição do DataServer a partir de um array.
     *
     * Cria um documento XML com o nome da tabela informado e adiciona elementos para cada campo
     * contido no array $xmlArray.
     *
     * @param array $data Array associativo contendo os dados a serem convertidos em XML, estruturado com a chave 'root' para o elemento raiz e 'TABELA' para os dados.
     * @return void
     */
    public function setXMLFromArray(array $xmlArray): void
    {
        if (empty($xmlArray['root'])) {
            throw new \InvalidArgumentException('Root não informado');
        }

        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $root = $dom->createElement($xmlArray['root']);
        $dom->appendChild($root);

        if (!empty($xmlArray['TABELA']) && is_array($xmlArray['TABELA'])) {
            $this->appendTabela($dom, $root, $xmlArray['TABELA']);
        }

        $this->xml = $dom->saveXML();
    }

    /**
     * Retorna o XML gerado pelo método setXML.
     *
     * @return string
     */
    public function getXML(): string
    {
        return $this->xml;
    }

    private function appendTabela(
        \DOMDocument $dom,
        \DOMElement $parent,
        array $tabela
    ): void {
        foreach ($tabela as $tag => $conteudo) {

            // Nó repetível
            if (is_array($conteudo) && array_is_list($conteudo)) {
                foreach ($conteudo as $item) {
                    $node = $dom->createElement($tag);
                    $this->appendCampos($dom, $node, $item);
                    $parent->appendChild($node);
                }
                continue;
            }

            // Nó único
            if (is_array($conteudo)) {
                $node = $dom->createElement($tag);
                $this->appendCampos($dom, $node, $conteudo);
                $parent->appendChild($node);
            }
        }
    }

    private function appendCampos(
        \DOMDocument $dom,
        \DOMElement $parent,
        array $campos
    ): void {
        foreach ($campos as $campo => $valor) {
            $parent->appendChild(
                $dom->createElement($campo, (string)$valor)
            );
        }
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
        $params = [
            'DataServerName'    => $this->dataServer,
            'XML'               => $this->xml,
            'Contexto'          => $this->contexto
        ];
        return $this->callWebServiceMethod('SaveRecord', $params, '');
    }

    /**
     * Lê um registro específico do DataServer, utilizando a chave primária definida.
     *
     * @return array Array associativo contendo os dados do registro lido.
     */
    public function readRecord(): array
    {
        $params = [
            'DataServerName'    => $this->dataServer,
            'PrimaryKey'        => $this->primaryKey,
            'Contexto'          => $this->contexto
        ];
        $result = $this->callWebServiceMethod('ReadRecord', $params, null);
        return Serialize::result($result);
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
        $params = [
            'DataServerName'    => $this->dataServer,
            'Filtro'            => $this->filtro,
            'Contexto'          => $this->contexto
        ];
        $result = $this->callWebServiceMethod('ReadView', $params, null);
        return Serialize::result($result);
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
        $params = [
            'DataServerName'    => $this->dataServer,
            'XML'               => $this->xml,
            'Contexto'          => $this->contexto
        ];
        return (int) $this->callWebServiceMethod('DeleteRecord', $params, 0);
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
        $params = [
            'DataServerName'    => $this->dataServer,
            'PrimaryKey'        => $this->primaryKey,
            'Contexto'          => $this->contexto,
        ];
        $result = $this->callWebServiceMethod('DeleteRecordByKey', $params, null);
        return Serialize::result($result);
    }


}
