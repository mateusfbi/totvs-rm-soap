<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Connection\WebService;
use TotvsRmSoap\Utils\Serialize;
use \SimpleXMLElement;
use \DOMDocument;

class Report
{
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
    private $connection;

    public function __construct(WebService $ws)
    {
        $this->connection = $ws->getClient('/wsReport/MEX?wsdl');
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
     * @param int $id
     * @return void
     */

    public function setId(int $id): void
    {
        $this->id = $id;
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
     * @param array $Parametros
     * @return void
     */

    public function setParametros(array $params = []): void
    {

        $xml = new SimpleXMLElement('<ArrayOfRptParameterReportPar></ArrayOfRptParameterReportPar>');
        $xml->addAttribute('xmlns#i', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->addAttribute('xmlns','http://www.totvs.com.br/RM/');

        // Percorre o array
        foreach ($params as $item) {
            // Adiciona um nó <RptParameterReportPar> para cada item
            $node = $xml->addChild('RptParameterReportPar');
            $node->addChild('Description', htmlspecialchars($item['Description']));
            $node->addChild('ParamName', htmlspecialchars($item['ParamName']));

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

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML(        str_replace('#',':',$xml->asXML()));
        //var_dump($dom->saveXML());
        // Retorna o XML como string
        $this->parametros = (string)$dom->saveXML();
    }

    /**
     * @param string $nomeArquivo
     * @return void
     */

    public function setNomeArquivo(string $nomeArquivo): void
    {
        $this->nomeArquivo = $nomeArquivo;
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
     * @param int $idReport
     * @return void
     */

    public function setIdReport(int $idReport): void
    {
        $this->idReport = $idReport;
    }

    /**
     * @param string $guid
     * @return void
     */

    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    /**
     * @param int $length
     * @return void
     */

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    /**
     * @param int $length
     * @return void
     */

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }


    /**
     * @return array
     */

    public function getReportList(): array
    {

        try {

            $execute = $this->connection->GetReportList([
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
     * @return string
     */

    public function generateReport(): string
    {

        try {

            $execute = $this->connection->GenerateReport([
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
     * @return string
     */

    public function generateReportAsynchronous(): string
    {

        try {

            $execute = $this->connection->GenerateReportAsynchronous([
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
     * @return string
     */

    public function getReportMetaData(): string
    {

        try {

            $execute = $this->connection->GetReportMetaData([
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
     * @return array
     */

    public function getReportInfo(): array
    {
        try {

            $execute = $this->connection->GetReportInfo([
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
     * @return string
     */

    public function getGeneratedReportStatus(): string
    {

        try {

            $execute = $this->connection->GetGeneratedReportStatus([
                'id'      => $this->id
            ]);

            $return = $execute->GetGeneratedReportStatusResult;
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }

    /**
     * @return int
     */

    public function getGeneratedReportSize(): int
    {

        try {

            $execute = $this->connection->GetGeneratedReportSize([
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
     * @return string
     */

    public function getFileHash(): string
    {

        try {

            $execute = $this->connection->GetFileHash([
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
     * @return string
     */

    public function getFileChunk(): string
    {

        try {

            $execute = $this->connection->GetFileChunk([
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
