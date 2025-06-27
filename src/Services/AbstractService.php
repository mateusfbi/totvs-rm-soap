<?php

namespace mateusfbi\TotvsRmSoap\Services;

use SoapClient;
use RuntimeException;

/**
 * Classe Abstrata AbstractService
 *
 * Fornece uma base comum para todas as classes de serviço SOAP, unificando
 * o cliente SOAP e o método auxiliar para chamadas de serviço web.
 *
 * @package mateusfbi\TotvsRmSoap\Services
 */
abstract class AbstractService
{
    /**
     * @var SoapClient O cliente SOAP configurado para o serviço específico.
     */
    protected SoapClient $webService;

    /**
     * Método auxiliar para chamar métodos do serviço web e tratar exceções.
     *
     * @param string $methodName Nome do método a ser chamado no serviço web.
     * @param array $params Parâmetros a serem passados para o método.
     * @param mixed $defaultValue Valor padrão a ser retornado em caso de erro. Se null, uma RuntimeException será lançada.
     * @return mixed O resultado da chamada do método ou o valor padrão em caso de exceção.
     * @throws RuntimeException Se houver um erro na conexão com o servidor SOAP e defaultValue for null.
     */
    protected function callWebServiceMethod(string $methodName, array $params = [], $defaultValue = null)
    {
        try {
            $execute = $this->webService->$methodName($params);
            $resultProperty = $methodName . 'Result';
            return $execute->$resultProperty;
        } catch (\Exception $e) {
            $errorMessage = "Erro ao chamar o método SOAP '{$methodName}' na classe " . static::class . ": " . $e->getMessage();
            error_log($errorMessage);
            if ($defaultValue === null) {
                throw new RuntimeException($errorMessage, 0, $e);
            }
            return $defaultValue;
        }
    }
}