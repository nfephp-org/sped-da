<?php

namespace NFePHP\DA\Common;

use stdClass;

class NfeStd
{
    /**
     * Converte o xml em um stdClass
     * @param string|null $xml
     * @return stdClass
     * @throws \Exception
     */
    public static function toStd(?string $xml = null): stdClass
    {
        if (empty($xml)) {
            throw new \Exception("O XML está vazio.");
        }
        if ($xml[0] !== '<') {
            throw new \Exception("XML mal formatado ou não é um XML.");
        }
        $node = self::getNode($xml);
        $sxml = simplexml_load_string($node);
        $json = str_replace(
            '@attributes',
            'attributes',
            json_encode($sxml, JSON_PRETTY_PRINT)
        );
        $std = json_decode($json);
        if (!is_object($std)) {
            //não é um objeto entao algum erro ocorreu
            throw new \Exception("Falhou a converção para stdClass. Documento: $xml");
        }
        return $std;
    }

    /**
     * @param string $xml
     * @return false|string|null
     */
    protected static function getNode(string $xml)
    {
        $rootTagList = [
            'nfeProc',
            'NFe'
        ];
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        try {
            $dom->loadXML($xml);
        } catch (\Exception $e) {
            return null;
        }
        foreach ($rootTagList as $key) {
            $node = !empty($dom->getElementsByTagName($key)->item(0))
                ? $dom->getElementsByTagName($key)->item(0)
                : '';
            if (!empty($node)) {
                return $dom->saveXML($node);
            }
        }
        return null;
    }

    /**
     * Return QRCODE and urlChave from XML
     * @return array
     */
    private static function getQRCode($xml): array
    {
        $resp = [
            'qrCode' => '',
            'urlChave' => ''
        ];
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($xml);
        $node = $dom->getElementsByTagName('infNFeSupl')->item(0);
        if (!empty($node)) {
            $resp = [
                'qrCode' => $node->getElementsByTagName('qrCode')->item(0)->nodeValue,
                'urlChave' => $node->getElementsByTagName('urlChave')->item(0)->nodeValue
            ];
        }
        return $resp;
    }

}
