<?php

namespace NFePHP\DA\Tests\CTe;

use NFePHP\DA\CTe\Dacte;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

/**
 * DACTE de CT-e emitido em contingência off-line (tpEmis=2, NT CT-e 2026.004 v1.00).
 * O fixture cte_offline_sem_prot.xml é o examples/cte/fixtures/cte_hom_sem_prot.xml com
 * tpEmis=2, chave refeita, dhCont/xJust e um sign fictício no QR Code (o DACTE não confere).
 */
class DacteTest extends TestCase
{
    /**
     * @return string
     */
    private function textoDoDacteOffline(): string
    {
        $dacte = new Dacte(file_get_contents(TEST_FIXTURES . 'xml/cte_offline_sem_prot.xml'));
        return $this->normaliza(Utils::textoPdf($dacte->render()));
    }

    /**
     * Junta as quebras de linha do texto extraído do PDF
     * @param string $texto
     * @return string
     */
    private function normaliza(string $texto): string
    {
        return preg_replace('/\s+/u', ' ', $texto);
    }

    /**
     * Quantas vezes a marca "SEM VALOR FISCAL" aparece fora do xNome de homologação
     * ("CT-E EMITIDO EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL", exigido pela SEFAZ)
     * @param string $texto
     * @return int
     */
    private function marcasSemValorFiscal(string $texto): int
    {
        return substr_count($texto, 'SEM VALOR FISCAL')
            - substr_count($texto, 'AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL');
    }

    /**
     * O DACTE em contingência off-line acompanha a carga antes da autorização:
     * não pode sair com a marca de "SEM VALOR FISCAL"
     */
    public function test_offline_sem_protocolo_nao_imprime_sem_valor_fiscal(): void
    {
        $texto = $this->textoDoDacteOffline();
        $this->assertSame(0, $this->marcasSemValorFiscal($texto));
        $this->assertStringNotContainsString('NÃO PROTOCOLADO', $texto);
    }

    public function test_offline_imprime_texto_da_contingencia_com_dhcont_e_xjust(): void
    {
        $texto = $this->textoDoDacteOffline();
        $this->assertStringContainsString('CONTINGÊNCIA OFF-LINE', $texto);
        $this->assertStringContainsString('SEFAZ AUTORIZADORA INDISPONIVEL NO MOMENTO DA EMISSAO', $texto);
        $this->assertStringNotContainsString('CONTINGÊNCIA FS', $texto);
    }

    /**
     * Sem protocolo e fora da contingência off-line, o comportamento continua o mesmo
     */
    public function test_normal_sem_protocolo_continua_sem_valor_fiscal(): void
    {
        $xml = file_get_contents(dirname(__DIR__, 2) . '/examples/cte/fixtures/cte_hom_sem_prot.xml');
        $texto = $this->normaliza(Utils::textoPdf((new Dacte($xml))->render()));
        $this->assertSame(1, $this->marcasSemValorFiscal($texto));
        $this->assertStringContainsString('NÃO PROTOCOLADO', $texto);
    }
}
