<?php

namespace mateusfbi\TotvsRmSoap\Services;

use mateusfbi\TotvsRmSoap\Services\AbstractService;

use mateusfbi\TotvsRmSoap\Connection\WebService;
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

class Report extends AbstractService
{
    private int $coligada;
    private int $id;
    private string $filtro;
    private string $parametros;
    private string $nomeArquivo;
    private string $contexto;
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
		// 1. Inicializa o objeto DOMDocument
		$dom = new DOMDocument('1.0', 'utf-16');
		$dom->formatOutput = true; // ATENÇÃO: Para produção, considere definir como false para otimização de performance (reduz tamanho do XML).
		$dom->preserveWhiteSpace = false;

		// 2. Cria o elemento raiz <ArrayOfRptFilterReportPar> com seus namespaces
		$root = $dom->createElement('ArrayOfRptFilterReportPar');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:i', 'http://www.w3.org/2001/XMLSchema-instance');
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.totvs.com.br/RM/');
		$dom->appendChild($root);

		// 3. Percorre o array de entrada para criar cada bloco do XML
		foreach ($filtros as $filtroData) {
		    $rptFilterReportPar = $dom->createElement('RptFilterReportPar');

		    // Adiciona a tag <BandName>
		    $rptFilterReportPar->appendChild($dom->createElement('BandName', $filtroData['bandname']));

		    // Cria a tag <FiltersByTable> e seus filhos
		    $filtersByTable = $dom->createElement('FiltersByTable');
		    $valueContent = ''; // Inicializa o conteúdo para a tag <Value>

		    if (!empty($filtroData['filtersbytable'])) {
		        $allFilters = [];
		        foreach ($filtroData['filtersbytable'] as $tableFilter) {
		            $rptFilterByTablePar = $dom->createElement('RptFilterByTablePar');

		            $filterElement = $dom->createElement('Filter');
		            // Usa CDATA para o conteúdo do filtro
		            $filterElement->appendChild($dom->createCDATASection($tableFilter['filter']));
		            $rptFilterByTablePar->appendChild($filterElement);

		            $rptFilterByTablePar->appendChild($dom->createElement('Name', $tableFilter['name']));
		            $rptFilterByTablePar->appendChild($dom->createElement('TableName', $tableFilter['tablename']));

		            $filtersByTable->appendChild($rptFilterByTablePar);

		            // Acumula os filtros para usar na tag <Value>
		            $allFilters[] = $tableFilter['filter'];
		        }
		        // Constrói o conteúdo da tag <Value>
		        $valueContent = '(' . implode(' AND ', $allFilters) . ')';
		    }
		    $rptFilterReportPar->appendChild($filtersByTable);

		    // Adiciona a tag <MainFilter>
		    $rptFilterReportPar->appendChild($dom->createElement('MainFilter', $filtroData['mainfilter'] ? 'true' : 'false'));

		    // Adiciona a tag <Value> com o conteúdo gerado
		    $valueElement = $dom->createElement('Value');
		    if (!empty($valueContent)) {
		        $valueElement->appendChild($dom->createCDATASection($valueContent));
		    }
		    $rptFilterReportPar->appendChild($valueElement);

		    // Adiciona o bloco completo ao elemento raiz
		    $root->appendChild($rptFilterReportPar);
		}

		// 4. Retorna a string XML completa
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
        $dom->formatOutput = true; // ATENÇÃO: Para produção, considere definir como false para otimização de performance (reduz tamanho do XML).
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
     * Obtém a lista de relatórios disponíveis.
     *
     * Executa o método GetReportList do serviço SOAP e processa o resultado,
     * retornando em formato de array associativo.
     *
     * @return array Lista de relatórios com campos como coligada, sistema, id, código, nome, data e uuid.
     */
    public function getReportList(): array
    {
        // ATENÇÃO: A análise desta string é frágil e depende de um formato exato.
        // Se possível, verifique se o serviço SOAP pode retornar os dados em XML
        // para uma análise mais robusta e menos propensa a erros.
        $rawResult = $this->callWebServiceMethod('GetReportList', ['codColigada' => $this->coligada], '');
        $return = [];

        if (!empty($rawResult)) {
            // Remove o prefixo de serialização PHP, se presente (ex: s:7625:")
            $result = preg_replace('/^s:\d+:"/', '', $rawResult);

            $registros = explode(';,', $result);

            foreach ($registros as $registro) {
                $registro = trim($registro);
                if (empty($registro)) continue;

                $campos = explode(',', $registro);

                // Garante que há campos suficientes antes de tentar acessá-los
                if (count($campos) >= 6) { // coligada, sistema, id, codigo, nome (pelo menos 1 parte), data, uuid
                    $dados = [
                        'coligada' => trim($campos[0]),
                        'sistema' => trim($campos[1]),
                        'id' => trim($campos[2]),
                        'codigo' => trim($campos[3]),
                        // O nome pode conter vírgulas, então pegamos tudo entre o 4º campo e os dois últimos
                        'nome' => trim(implode(', ', array_slice($campos, 4, -2))),
                        'data' => trim($campos[count($campos) - 2]),
                        'uuid' => trim($campos[count($campos) - 1])
                    ];
                    $return[] = $dados;
                } else {
                    // Logar ou lidar com registros mal formatados
                    error_log("Registro de relatório mal formatado encontrado: " . $registro);
                }
            }
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
        $params = [
            'codColigada' => $this->coligada,
            'id'          => $this->id,
            'filters'     => empty($this->filtro) ? null : $this->filtro,
            'parameters'  => empty($this->parametros) ? null : $this->parametros,
            'fileName'    => $this->nomeArquivo,
            'contexto'    => empty($this->contexto) ? null : $this->contexto,
        ];
        return $this->callWebServiceMethod('GenerateReport', $params, '');
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
        $params = [
            'codColigada' => $this->coligada,
            'id'          => $this->id,
            'filters'     => $this->filtro,
            'parameters'  => $this->parametros,
            'fileName'    => $this->nomeArquivo,
            'contexto'    => $this->contexto,
        ];
        return $this->callWebServiceMethod('GenerateReportAsynchronous', $params, '');
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
        $params = [
            'codColigada' => $this->coligada,
            'id'      => $this->id
        ];
        return $this->callWebServiceMethod('GetReportMetaData', $params, '');
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
        $params = [
            'codColigada'      => $this->coligada,
            'idReport'      => $this->id
        ];
        $result = $this->callWebServiceMethod('GetReportInfo', $params, null);
        return isset($result->string) ? $result->string : [];
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
        $params = [
            'id'      => $this->guid
        ];
        return $this->callWebServiceMethod('GetGeneratedReportStatus', $params, '');
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
        $params = [
            'guid'      => $this->guid
        ];
        return (int) $this->callWebServiceMethod('GetGeneratedReportSize', $params, 0);
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
        $params = [
            'guid'      => $this->guid
        ];
        return $this->callWebServiceMethod('GetFileHash', $params, '');
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
        $params = [
            'guid'      => $this->guid,
            'offset'      => $this->offset,
            'length'      => $this->length
        ];
        return $this->callWebServiceMethod('GetFileChunk', $params, '');
    }
}
