<?php

namespace NFePHP\DA\Tests\MDFe;

use NFePHP\DA\MDFe\Damdfe;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

class DamdfeSeguroValePedagioTest extends TestCase
{
    /**
     * @dataProvider orientacoes
     */
    public function testCombinaSegurosEValePedagioComCpfEValorZero($orientacao): void
    {
        $dom = new \DOMDocument();
        $dom->load(TEST_FIXTURES . 'xml/mdfe_seguro.xml');
        $vales = new \DOMDocument();
        $vales->load(TEST_FIXTURES . 'xml/mdfe_vale_pedagio_valor.xml');
        $valePed = $dom->importNode($vales->getElementsByTagName('valePed')->item(0), true);
        $pagador = $valePed->getElementsByTagName('CNPJPg')->item(0);
        $cpf = $dom->createElementNS($pagador->namespaceURI, 'CPFPg', '12345678901');
        $pagador->parentNode->replaceChild($cpf, $pagador);
        $valePed->getElementsByTagName('vValePed')->item(0)->nodeValue = '0.00';
        $dom->getElementsByTagName('infANTT')->item(0)->appendChild($valePed);
        $damdfe = new Damdfe($dom->saveXML());
        $damdfe->printParameters($orientacao);
        $texto = preg_replace('/\s+/', ' ', Utils::textoPdf($damdfe->render()));

        foreach (['12345678901', '9000000001', '0,00', '1.234,50', 'Valor R$',
            'SEGURADORA ALFA', 'SEGURADORA BETA', '2355523235325001', '2355523235325002'] as $valor) {
            $this->assertStringContainsString($valor, $texto);
        }
        $this->assertSame(1, substr_count($texto, 'Responsável pelo Seguro: Emitente do MDF-e'));
        $this->assertSame(1, substr_count($texto, 'Responsável pelo Seguro: Contratante'));
    }

    public function testSegurosSemSeguradoraNemApoliceNaoReutilizamDados(): void
    {
        $dom = new \DOMDocument();
        $dom->load(TEST_FIXTURES . 'xml/mdfe_seguro.xml');
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('m', 'http://www.portalfiscal.inf.br/mdfe');
        foreach ($xpath->query('//m:seg/m:infSeg | //m:seg/m:nApol') as $node) {
            $node->parentNode->removeChild($node);
        }
        $texto = Utils::textoPdf((new Damdfe($dom->saveXML()))->render());

        $this->assertStringContainsString('Responsável pelo Seguro: Emitente do MDF-e', $texto);
        $this->assertStringContainsString('Responsável pelo Seguro: Contratante', $texto);
        $this->assertStringNotContainsString('SEGURADORA ALFA', $texto);
        $this->assertStringNotContainsString('SEGURADORA BETA', $texto);
        $this->assertStringNotContainsString('2355523235325001', $texto);
        $this->assertStringNotContainsString('2355523235325002', $texto);
    }

    public function orientacoes(): array
    {
        return [['P'], ['L']];
    }
}
