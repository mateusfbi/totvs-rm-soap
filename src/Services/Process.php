<?php

namespace TotvsRmSoap\Services;

use TotvsRmSoap\Connection\WebService;

class Process
{
    private string $process;
    private string $xml;
    private string $jobId;
    private string $execId;
    private $connection;

    public function __construct(WebService $ws)
    {
        $this->connection = $ws->getClient('/wsProcess/MEX?wsdl');
    }

    /**
     * @param string $process
     * @return void
     */

    public function setProcess(string $process): void
    {
        $this->process = $process;
    }

    /**
     * @param string $xml
     * @return void
     */

    public function setXML(string $xml): void
    {
        $this->xml = $xml;
    }

    /**
     * @param string $jobId
     * @return void
     */

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    /**
     * @param string $jobId
     * @return void
     */

    public function setExecId(string $execId): void
    {
        $this->execId = $execId;
    }
    /**
     * @return int
     */

    public function executeWithXmlParams(): int
    {

        try {

            $execute = $this->connection->ExecuteWithXmlParams([
                'ProcessServerName' => $this->process,
                'strXmlParams'      => $this->xml
            ]);

            $return = $execute->ExecuteWithXmlParamsResult;

        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $return;
    }
    
    public function getProcessStatus(): int
    {

        try {

            $execute = $this->connection->getProcessStatus([
                'jobId'  => $this->jobId,
                'execId' => $this->execId
            ]);

            $return = $execute->GetProcessStatusResult;

        } catch (\Exception $e) {
            echo '<br /><br /> ' . $e->getMessage() . PHP_EOL;
        }

        return $return;
    }
}
