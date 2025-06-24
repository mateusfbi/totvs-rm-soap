<?php

namespace mateusfbi\TotvsRmSoap\Utils;

/**
 * Classe Serialize
 *
 * Responsável por converter respostas em XML (geralmente obtidas via SOAP)
 * para um array associativo do PHP.
 *
 * Este método utiliza a função simplexml_load_string para transformar o XML
 * em um objeto, converte-o para JSON e, em seguida, decodifica-o para um array.
 *
 * @package TotvsRmSoap\Utils
 */
class Serialize
{
    /**
     * Converte a resposta SOAP em XML para um array associativo.
     *
     * @param mixed $response Resposta SOAP em formato XML.
     * @return array Array associativo resultante da conversão.
     */
    public static function result($response): array
    {
        return json_decode(json_encode(simplexml_load_string($response)), true);
    }
}
