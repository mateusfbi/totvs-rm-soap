<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Connection\WebService;
use TotvsRmSoap\Utils\Serialize;
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
 * @package TotvsRmSoap\Services
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
     * Define o filtro a ser aplicado na requisição do relatório.
     *
     * @param string $filtro Filtro utilizado para consulta ou execução.
     * @return void
     */
    public function setFiltro(string $filtro): void
    {
        $this->filtro = $filtro;
    }

    /**
     * Gera o XML de parâmetros do relatório.
     *
     * Este método percorre um array de itens e monta a estrutura XML
     * que será enviada ao serviço SOAP. Cada item do array deve conter as chaves:
     * - Description
     * - ParamName
     * - Type (String, Int16, Int32 ou DateTime)
     * - Value
     *
     * Para cada item, é criado um nó <RptParameterReportPar> com informação dos
     * atributos, tipo e valor, seguindo o padrão esperado pelo serviço SOAP.
     *
     * @param array $params Array contendo os dados necessários para gerar o XML.
     * @return void
     */
    public function setParametros(array $params = []): void
    {

        $xml = new SimpleXMLElement('<ArrayOfRptParameterReportPar></ArrayOfRptParameterReportPar>');
        $xml->addAttribute('xmlns#i', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute('xmlns', 'http://www.totvs.com.br/RM/');

        // Percorre o array
        foreach ($params as $item) {
            // Adiciona um nó <RptParameterReportPar> para cada item
            $node = $xml->addChild('RptParameterReportPar');
            $node->addChild('Description', htmlspecialchars($item['Description']));
            $node->addChild('ParamName', htmlspecialchars($item['ParamName']));

            // Determina o tipo e o valueType de acordo com o campo Type
            switch ($item['Type']) {
                case 'String':
                    $type = 'System.String';
                    $valueType = 'string';
                    break;
                case 'Int16':
                    $type = 'System.Int16';
                    $valueType = 'int';
                    break;
                case 'Int32':
                    $type = 'System.Int32';
                    $valueType = 'int';
                    break;
                case 'DateTime':
                    $type = 'System.DateTime';
                    $valueType = 'dateTime';
                    break;
            }
            // Adiciona a estrutura <Type>
            $typeNode = $node->addChild('Type');
            $typeNode->addAttribute("xmlns#d3p1", "http://schemas.datacontract.org/2004/07/System");
            $typeNode->addAttribute("xmlns#d3p2", '-mscorlib, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089-System-System.RuntimeType');
            $typeNode->addAttribute('i#type', 'd3p2:RuntimeType');
            $typeNode->addAttribute('xmlns#d3p3', '-mscorlib, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089-System-System.UnitySerializationHolder');
            $typeNode->addAttribute('z#FactoryType', 'd3p3:UnitySerializationHolder');
            $typeNode->addAttribute('xmlns#z', 'http://schemas.microsoft.com/2003/10/Serialization/');

            // Adiciona os subelementos de <Type>
            $dataNode = $typeNode->addChild('Data', $type);
            $dataNode->addAttribute('xmlns#d4p1', 'http://www.w3.org/2001/XMLSchema');
            $dataNode->addAttribute('i#type', 'd4p1:string');
            $dataNode->addAttribute('xmlns', '');

            $unityTypeNode = $typeNode->addChild('UnityType', '4');
            $unityTypeNode->addAttribute('xmlns#d4p1', 'http://www.w3.org/2001/XMLSchema');
            $unityTypeNode->addAttribute('i#type', 'd4p1:int');
            $unityTypeNode->addAttribute('xmlns', '');

            $assemblyNameNode = $typeNode->addChild('AssemblyName', 'mscorlib, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089');
            $assemblyNameNode->addAttribute('xmlns#d4p1', 'http://www.w3.org/2001/XMLSchema');
            $assemblyNameNode->addAttribute('i#type', 'd4p1:string');
            $assemblyNameNode->addAttribute('xmlns', '');

            // Adiciona o elemento <Value>
            $valueNode = $node->addChild('Value', htmlspecialchars($item['Value']));
            $valueNode->addAttribute('xmlns#d3p1', 'http://www.w3.org/2001/XMLSchema');
            $valueNode->addAttribute('i#type', 'd3p1:' . $valueType);

            $node->addChild('Visible', 'true');
        }

        // Formata o XML para melhorar a legibilidade (whitespace e quebras de linha)
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML(str_replace('#', ':', $xml->asXML()));
        // Retorna o XML como string
        $this->parametros = (string)$dom->saveXML();
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
