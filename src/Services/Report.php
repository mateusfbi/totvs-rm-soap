<?php

namespace mateusfbi\TotvsRmSoap\Services;

use mateusfbi\TotvsRmSoap\Connection\WebService;
use \SimpleXMLElement;
use \DOMDocument;

/**
 * Classe Report
 *
 * Responsável por gerar e manipular o XML de parâmetros dos relatórios a serem enviados via serviço SOAP.
 *
 * Essa classe permite:
 * - Configurar dados como coligada, id, filtros, parâmetros XML, nome do arquivo e contexto.
 * - Gerar o XML de parâmetros dos relatórios a partir de um array de itens.
 * - Invocar os métodos do serviço SOAP para realizar operações relacionadas a relatórios, como:
 *   - Gerar o relatório.
 *   - Obter a lista de relatórios.
 *   - Obter status, metadados, informações, tamanho, hash e chunks do arquivo gerado.
 *
 * @package mateusfbi\TotvsRmSoap\Services
 */

class Report
{
    private $webService;
    private int $coligada;
    private int $id;
    private string $filtro;
    private string $parametros;
    private string $nomeArquivo;
    private string $contexto;
    private int $idReport;
    private int $offset;
    private int $length;
    private string $guid;

    /**
     * Construtor do Report.
     *
     * Inicializa a instância do Report com o cliente SOAP obtido do WebService.
     *
     * @param WebService $webService Instância do serviço web.
     */
    public function __construct(WebService $webService)
    {
        $this->webService = $webService->getClient('/wsReport/MEX?wsdl');
    }

    /**
     * Define o código da coligada.
     *
     * @param int $coligada Código da coligada.
     * @return void
     */
    public function setColigada(int $coligada): void
    {
        $this->coligada = $coligada;
    }

    /**
     * Define o identificador do relatório.
     *
     * @param int $id Identificador do relatório.
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

   	 /**
     * Gera o XML de filtros do relatório.
     *
     * Este método percorre um array de itens e monta a estrutura XML
     * que será enviada ao serviço SOAP.
     *
     * @param array[] $filtros Array de filtros. Cada filtro deve ser um array associativo com a seguinte estrutura:
     * [
     * 'bandname' => (string) Nome da banda do relatório.
     * 'mainfilter' => (bool) Se é o filtro principal.
     * 'filtersbytable' => (array[]) Lista de sub-filtros, onde cada um é um array com:
     * [
     * 'filter' => (string) A condição de filtro SQL.
     * 'name' => (string) Nome do filtro (opcional).
     * 'tablename' => (string) Nome da tabela alvo.
     * ]
     * ]
     * @return void
     */
    public function setFiltro(array $filtros = []): void
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;

        $nsRM = 'http://www.totvs.com.br/RM/';
        $nsI = 'http://www.w3.org/2001/XMLSchema-instance';

        // Cria o elemento raiz com seu namespace
        $root = $dom->createElementNS($nsRM, 'ArrayOfRptFilterReportPar');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:i', $nsI);
        $dom->appendChild($root);

        foreach ($filtros as $filtroData) {
            // Cria o nó <rptfilterreportpar> dentro do namespace padrão do root
            $filtroXml = $dom->createElement('rptfilterreportpar');

            $filtroXml->appendChild($dom->createElement('bandname', $filtroData['bandname']));
            $filtroXml->appendChild($dom->createElement('mainfilter', $filtroData['mainfilter'] ? 'true' : 'false'));

            $filtersByTableXml = $dom->createElement('filtersbytable');
            $valueParts = [];

            if (!empty($filtroData['filtersbytable'])) {
                foreach ($filtroData['filtersbytable'] as $subFiltroData) {
                    $subFiltroXml = $dom->createElement('rptfilterbytablepar');
                    $subFiltroXml->appendChild($dom->createElement('filter', $subFiltroData['filter']));
                    $subFiltroXml->appendChild($dom->createElement('name', $subFiltroData['name']));
                    $subFiltroXml->appendChild($dom->createElement('tablename', $subFiltroData['tablename']));
                    $filtersByTableXml->appendChild($subFiltroXml);

                    $valueParts[] = '(' . $subFiltroData['filter'] . ')';
                }
            }
            $filtroXml->appendChild($filtersByTableXml);

            $valueString = implode(' AND ', $valueParts);
            if (count($valueParts) > 1) {
                $valueString = '(' . $valueString . ')';
            }
            $filtroXml->appendChild($dom->createElement('value', $valueString));

            $root->appendChild($filtroXml);
        }
		
        $this->filtro = $dom->saveXML();
    }

	/**
     * Gera o XML de parâmetros do relatório usando DOMDocument para maior robustez.
     *
     * Este método percorre um array de itens e monta a estrutura XML
     * que será enviada ao serviço SOAP. Cada item do array deve conter as chaves:
     * - Description
     * - ParamName
     * - Type (String, Int16, Int32 ou DateTime)
     * - Value
     *
     * @param array $params Array contendo os dados necessários para gerar o XML.
     * @return void
     */
    public function setParametros(array $params = []): void
    {
        // 1. Inicializa o DOMDocument, a ferramenta correta para XML complexo.
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true; // Formata a saída para ser legível.
        $dom->preserveWhiteSpace = false;

        // 2. Define os URIs dos namespaces para reutilização e legibilidade.
        $nsRM = 'http://www.totvs.com.br/RM/';
        $nsI = 'http://www.w3.org/2001/XMLSchema-instance';
        $nsSystem = 'http://schemas.datacontract.org/2004/07/System';
        $nsMscorlib = '-mscorlib, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089-System-System.RuntimeType';
        $nsUnityHolder = '-mscorlib, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089-System-System.UnitySerializationHolder';
        $nsSerialization = 'http://schemas.microsoft.com/2003/10/Serialization/';
        $nsSchema = 'http://www.w3.org/2001/XMLSchema';

        // 3. Cria o elemento raiz com seu namespace padrão.
        $root = $dom->createElementNS($nsRM, 'ArrayOfRptParameterReportPar');
        // Adiciona os namespaces ao elemento raiz de forma explícita e segura.
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:i', $nsI);
        $dom->appendChild($root);

        // Percorre o array de parâmetros
        foreach ($params as $item) {
            // Cria o nó principal <RptParameterReportPar> (dentro do namespace padrão $nsRM)
            $node = $dom->createElement('RptParameterReportPar');

            $node->appendChild($dom->createElement('Description', $item['Description']));
            $node->appendChild($dom->createElement('ParamName', $item['ParamName']));

            // --- Bloco <Type> ---
            $typeNode = $dom->createElement('Type');
            // Adiciona os atributos com seus prefixos e namespaces de forma correta
            $typeNode->setAttributeNS($nsI, 'i:type', 'd3p2:RuntimeType');
            $typeNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d3p1', $nsSystem);
            $typeNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d3p2', $nsMscorlib);
            $typeNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d3p3', $nsUnityHolder);
            $typeNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:z', $nsSerialization);
            $typeNode->setAttributeNS($nsSerialization, 'z:FactoryType', 'd3p3:UnitySerializationHolder');
            
            // Sub-elementos de <Type>
            $typeValue = '';
            $valueType = '';
            switch ($item['Type']) {
                case 'String': $typeValue = 'System.String'; $valueType = 'string'; break;
                case 'Int16': $typeValue = 'System.Int16'; $valueType = 'int'; break;
                case 'Int32': $typeValue = 'System.Int32'; $valueType = 'int'; break;
                case 'DateTime': $typeValue = 'System.DateTime'; $valueType = 'dateTime'; break;
            }

            // Para os filhos de <Type>, o namespace padrão é resetado (xmlns="").
            // Para isso, criamos os elementos sem namespace explícito.
            $dataNode = $dom->createElement('Data', $typeValue);
            $dataNode->setAttributeNS($nsI, 'i:type', 'd4p1:string');
            $dataNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d4p1', $nsSchema);
            $typeNode->appendChild($dataNode);

            $unityTypeNode = $dom->createElement('UnityType', '4');
            $unityTypeNode->setAttributeNS($nsI, 'i:type', 'd4p1:int');
            $unityTypeNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d4p1', $nsSchema);
            $typeNode->appendChild($unityTypeNode);

            $assemblyNameNode = $dom->createElement('AssemblyName', 'mscorlib, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089');
            $assemblyNameNode->setAttributeNS($nsI, 'i:type', 'd4p1:string');
            $assemblyNameNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d4p1', $nsSchema);
            $typeNode->appendChild($assemblyNameNode);

            $node->appendChild($typeNode); // Adiciona o bloco <Type> completo

            // --- Bloco <Value> ---
            $valueNode = $dom->createElement('Value', $item['Value']);
            $valueNode->setAttributeNS($nsI, 'i:type', 'd3p1:' . $valueType);
            $valueNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d3p1', $nsSchema);
            $node->appendChild($valueNode);
            
            $node->appendChild($dom->createElement('Visible', 'true'));

            $root->appendChild($node); // Adiciona o nó do parâmetro ao XML raiz
        }

        // 4. Salva o XML gerado na propriedade da classe.
        // O saveXML() do DOM já gera o XML completo e formatado.
        $this->parametros = $dom->saveXML();
    }

    /**
     * Define o nome do arquivo do relatório.
     *
     * @param string $nomeArquivo Nome do arquivo.
     * @return void
     */
    public function setNomeArquivo(string $nomeArquivo): void
    {
        $this->nomeArquivo = $nomeArquivo;
    }

    /**
     * Define o contexto da requisição.
     *
     * @param string $contexto Contexto da requisição.
     * @return void
     */
    public function setContexto(string $contexto): void
    {
        $this->contexto = $contexto;
    }

    /**
     * Define o identificador específico do relatório para requisições adicionais.
     *
     * @param int $idReport Identificador do relatório.
     * @return void
     */
    public function setIdReport(int $idReport): void
    {
        $this->idReport = $idReport;
    }

    /**
     * Define o GUID utilizado para obter informações do arquivo gerado.
     *
     * @param string $guid Identificador único (GUID).
     * @return void
     */
    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    /**
     * Define o tamanho do chunk de arquivo a ser recuperado.
     *
     * @param int $length Tamanho do chunk.
     * @return void
     */
    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    /**
     * Define o offset para a requisição de um chunk do arquivo.
     *
     * @param int $offset Offset do chunk.
     * @return void
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }


    /**
     * Obtém a lista de relatórios disponíveis.
     *
     * Executa o método GetReportList do serviço SOAP e processa o resultado,
     * retornando em formato de array associativo.
     *
     * @return array Lista de relatórios com campos como coligada, sistema, id, código, nome, data e uuid.
     */
    public function getReportList(): array
    {

        try {

            $execute = $this->webService->GetReportList([
                'codColigada' => $this->coligada
            ]);

            $result = str_replace('s:7625:"', '', $execute->GetReportListResult);

            $registros = explode(';,', $result);

            foreach ($registros as $registro) {
                $registro = trim($registro); // Remover espaços e quebras de linha
                if (empty($registro)) continue;
                // Dividir os campos por vírgula
                $campos = explode(',', $registro);
                // Mapear os campos (ajuste os índices conforme necessário)
                $dados = [
                    'coligada' => trim($campos[0]),
                    'sistema' => trim($campos[1]),
                    'id' => trim($campos[2]),
                    'codigo' => trim($campos[3]),
                    'nome' => trim(implode(', ', array_slice($campos, 4, -2))),
                    'data' => trim($campos[count($campos) - 2]),
                    'uuid' => trim($campos[count($campos) - 1])
                ];

                $return[] = $dados;
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * Gera o relatório através do serviço SOAP.
     *
     * Envia a requisição com os parâmetros configurados (coligada, id, filtro, parâmetros, nome do arquivo e contexto)
     * e retorna o resultado da execução.
     *
     * @return string Resultado da geração do relatório.
     */
    public function generateReport(): string
    {

        try {
            $execute = $this->webService->GenerateReport([
                'codColigada' => $this->coligada,
                'id'          => $this->id,
                'filters'     => empty($this->filtro) ? null : $this->filtro,
                'parameters'  => empty($this->parametros) ? null : $this->parametros,
                'fileName'    => $this->nomeArquivo,
                'contexto'    => empty($this->contexto) ? null : $this->contexto,
            ]);
            
            
            
            $return = $execute->GenerateReportResult;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * Gera o relatório de forma assíncrona através do serviço SOAP.
     *
     * Envia os mesmos parâmetros do método generateReport(), porém utilizando o método assíncrono do endpoint.
     *
     * @return string Resultado retornado pela execução assíncrona do relatório.
     */
    public function generateReportAsynchronous(): string
    {

        try {

            $execute = $this->webService->GenerateReportAsynchronous([
                'codColigada' => $this->coligada,
                'id'          => $this->id,
                'filters'     => $this->filtro,
                'parameters'  => $this->parametros,
                'fileName'    => $this->nomeArquivo,
                'contexto'    => $this->contexto,
            ]);

            $return = $execute->GenerateReportAsynchronousResult;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

   /**
     * Obtém os metadados do relatório.
     *
     * Envia uma requisição SOAP para recuperar os metadados do relatório com base nos parâmetros coligada e id.
     *
     * @return string Metadados do relatório em formato de string.
     */
    public function getReportMetaData(): string
    {

        try {

            $execute = $this->webService->GetReportMetaData([
                'codColigada' => $this->coligada,
                'id'      => $this->id
            ]);

            $return = $execute->GetReportMetaDataResult;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * Obtém informações adicionais do relatório.
     *
     * Executa uma requisição SOAP para recuperar informações do relatório baseado no idReport e retorna
     * o resultado como um array.
     *
     * @return array Informações do relatório ou array vazio em caso de falha.
     */
    public function getReportInfo(): array
    {
        try {

            $execute = $this->webService->GetReportInfo([
                'codColigada'      => $this->coligada,
                'idReport'      => $this->idReport
            ]);

            $result = $execute->GetReportInfoResult;
            $return = isset($result->string) ? $result->string : [];
        } catch (\Exception $e) {
            $return = [];
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * Obtém o status do relatório gerado.
     *
     * Envia uma requisição SOAP usando o identificador do relatório (id) para recuperar
     * o status do relatório gerado.
     *
     * @return string Status do relatório.
     */
    public function getGeneratedReportStatus(): string
    {

        try {

            $execute = $this->webService->GetGeneratedReportStatus([
                'id'      => $this->id
            ]);

            $return = $execute->GetGeneratedReportStatusResult;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * Obtém o tamanho do arquivo do relatório gerado.
     *
     * Envia uma requisição SOAP utilizando o GUID para recuperar o tamanho do arquivo.
     *
     * @return int Tamanho do arquivo do relatório.
     */
    public function getGeneratedReportSize(): int
    {

        try {

            $execute = $this->webService->GetGeneratedReportSize([
                'guid'      => $this->guid
            ]);

            $return = $execute->GetGeneratedReportSizeResult;
        } catch (\Exception $e) {
            $return = '';
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * Obtém o hash do arquivo do relatório.
     *
     * Envia uma requisição SOAP utilizando o GUID para recuperar o hash do arquivo.
     *
     * @return string Hash do arquivo.
     */
    public function getFileHash(): string
    {

        try {

            $execute = $this->webService->GetFileHash([
                'guid'      => $this->guid
            ]);

            $return = $execute->GetFileHashResult;
        } catch (\Exception $e) {
            $return = '';
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * Obtém um chunk (pedaço) do arquivo do relatório.
     *
     * Envia uma requisição SOAP utilizando o GUID, offset e length para recuperar um pedaço do arquivo.
     *
     * @return string Chunk do arquivo do relatório.
     */
    public function getFileChunk(): string
    {

        try {

            $execute = $this->webService->GetFileChunk([
                'guid'      => $this->guid,
                'offset'      => $this->offset,
                'length'      => $this->length
            ]);

            $return = $execute->GetFileChunkResult;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }
}
