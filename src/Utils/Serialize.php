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
        if (empty($response)) {
            return [];
        }

        // Desabilita erros de XML para que possamos tratá-los manualmente
        libxml_use_internal_errors(true);

        $xmlObject = simplexml_load_string($response);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                error_log("Erro ao carregar XML: " . $error->message);
            }
            libxml_clear_errors(); // Limpa os erros para não afetar futuras operações
            return [];
        }

        libxml_use_internal_errors(false); // Restaura o comportamento padrão

        return json_decode(json_encode($xmlObject), true);
    }
}
