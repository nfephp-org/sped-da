<?php

namespace NFePHP\DA\Tests\NFSe;

use NFePHP\DA\NFSe\Danfse;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

class DanfseTest extends TestCase
{
    public function testGeraDanfseV20NacionalEmPaginaUnicaComBlocosEQrcode(): void
    {
        $danfse = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));

        $pdf = $danfse->render();
        $parser = new Parser();
        $document = $parser->parseContent($pdf);
        $text = $this->normalize($document->getText());

        $this->assertCount(1, $document->getPages());
        $this->assertStringContainsString('DANFSe v2.0', $text);
        $this->assertStringContainsString('Documento Auxiliar da NFS-e', $text);
        $this->assertStringContainsString('12345678901234567890123456789012345678901234567890', $text);
        $this->assertStringNotContainsString('NFS12345678901234567890123456789012345678901234567890', $text);
        $this->assertStringContainsString('A autenticidade desta NFS-e pode ser verificada', $text);
        $this->assertStringContainsString('PRESTADOR / FORNECEDOR', $text);
        $this->assertStringContainsString('TOMADOR / ADQUIRENTE', $text);
        $this->assertStringContainsString('DESTINATÁRIO DA OPERAÇÃO', $text);
        $this->assertStringContainsString('INTERMEDIÁRIO DA OPERAÇÃO', $text);
        $this->assertStringContainsString('SERVIÇO PRESTADO', $text);
        $this->assertStringContainsString('TRIBUTAÇÃO MUNICIPAL (ISSQN)', $text);
        $this->assertStringContainsString('TRIBUTAÇÃO FEDERAL (EXCETO CBS)', $text);
        $this->assertStringContainsString('TRIBUTAÇÃO IBS / CBS', $text);
        $this->assertStringContainsString('VALOR TOTAL DA NFS-E', $text);
        $this->assertStringContainsString('BC ISSQN R$ 990,00', $text);
        $this->assertStringContainsString('Alíquota Aplicada 5,00%', $text);
        $this->assertStringContainsString('Total das Retenções (ISSQN / Federais) R$ 55,50', $text);
        $this->assertStringContainsString('Valor Líquido da NFS-e R$ 944,50', $text);
        $this->assertStringContainsString('INFORMAÇÕES COMPLEMENTARES', $text);
        $this->assertStringContainsString('Inf. Cont.: Contrato mensal CAMU-3127 | Doc. Ref.: PO-123', $text);
        $this->assertStringContainsString('Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012', $text);
    }

    public function testPriorizaValoresDiretosDoContextoAntesDeDescendentesDecorativos(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml');
        $xml = str_replace(
            '<prest>',
            '<prest><metadados><CNPJ>00000000000000</CNPJ>'
            . '<xNome>Valor decorativo que não deve aparecer</xNome></metadados>',
            $xml
        );

        $text = $this->textFromPdf((new Danfse($xml))->render());

        $this->assertStringContainsString('12.345.678/0001-95', $text);
        $this->assertStringContainsString('Prestador Nacional de Servicos Ltda', $text);
        $this->assertStringNotContainsString('00.000.000/0000-00', $text);
        $this->assertStringNotContainsString('Valor decorativo que não deve aparecer', $text);
    }

    public function testUsaIbscbsConsolidadoDaInfNfseQuandoInformado(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml');
        $xml = str_replace(
            "        </valores>\n    </infNFSe>",
            "        </valores>\n"
            . "        <IBSCBS>\n"
            . "            <valores>\n"
            . "                <cIndOp>9</cIndOp>\n"
            . "                <cLocalidadeIncid>3550308</cLocalidadeIncid>\n"
            . "                <xLocalidadeIncid>Sao Paulo</xLocalidadeIncid>\n"
            . "                <UF>SP</UF>\n"
            . "                <vCalcReeRepRes>20.00</vCalcReeRepRes>\n"
            . "                <vBC>777.77</vBC>\n"
            . "                <uf>\n"
            . "                    <pRedAliqUF>1.00</pRedAliqUF>\n"
            . "                    <pIBSUF>1.20</pIBSUF>\n"
            . "                    <pAliqEfetUF>1.10</pAliqEfetUF>\n"
            . "                </uf>\n"
            . "                <mun>\n"
            . "                    <pRedAliqMun>2.00</pRedAliqMun>\n"
            . "                    <pIBSMun>2.20</pIBSMun>\n"
            . "                    <pAliqEfetMun>2.10</pAliqEfetMun>\n"
            . "                </mun>\n"
            . "                <fed>\n"
            . "                    <pRedAliqCBS>3.00</pRedAliqCBS>\n"
            . "                    <pCBS>3.20</pCBS>\n"
            . "                    <pAliqEfetCBS>3.10</pAliqEfetCBS>\n"
            . "                </fed>\n"
            . "            </valores>\n"
            . "            <totCIBS>\n"
            . "                <gIBS>\n"
            . "                    <vIBSTot>88.00</vIBSTot>\n"
            . "                    <gIBSMunTot><vIBSMun>55.00</vIBSMun></gIBSMunTot>\n"
            . "                    <gIBSUFTot><vIBSUF>33.00</vIBSUF></gIBSUFTot>\n"
            . "                </gIBS>\n"
            . "                <gCBS><vCBS>77.00</vCBS></gCBS>\n"
            . "                <vTotNF>1109.50</vTotNF>\n"
            . "            </totCIBS>\n"
            . "        </IBSCBS>\n"
            . "    </infNFSe>",
            $xml
        );

        $text = $this->textFromPdf((new Danfse($xml))->render());

        $this->assertStringContainsString('Base de Cálculo Após Exclusões e Reduções R$ 777,77', $text);
        $this->assertStringContainsString('Alíquota - CBS 3,20%', $text);
        $this->assertStringContainsString('Valor Total Apurado - IBS R$ 88,00', $text);
        $this->assertStringContainsString('Valor Total Apurado - CBS R$ 77,00', $text);
        $this->assertStringContainsString('Total do IBS/CBS R$ 165,00', $text);
        $this->assertStringContainsString('Valor Líquido da NFS-e + IBS/CBS R$ 1.109,50', $text);
        $this->assertStringNotContainsString('Base de Cálculo Após Exclusões e Reduções R$ 985,00', $text);
    }

    public function testAplicaReticenciasEmCamposLongosSemReduzirFonteAbaixoDoMinimo(): void
    {
        $originalName = 'Prestador Nacional de Servicos Ltda';
        $longName = str_repeat('Prestador Nacional Muito Longo ', 8);
        $xml = str_replace(
            '<xNome>' . $originalName . '</xNome>',
            '<xNome>' . $longName . '</xNome>',
            file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')
        );

        $pdf = (new Danfse($xml))->render();
        $text = $this->textFromPdf($pdf);
        $operators = $this->decompressedPdfOperators($pdf);

        $this->assertMatchesRegularExpression('/Prestador Nacional Muito Longo .*\\.\\.\\./', $text);
        $this->assertStringNotContainsString($longName, $text);
        $this->assertGreaterThanOrEqual(6.0, $this->minimumFontSize($operators));
    }

    public function testAplicaSupressoesCamposSemInformacaoEOmiteTributacaoFederalApos2026(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml');
        $xml = preg_replace('/\s*<toma>.*?<\/toma>/s', '', $xml);
        $xml = preg_replace('/\s*<dest>.*?<\/dest>/s', '', $xml);
        $xml = preg_replace('/\s*<interm>.*?<\/interm>/s', '', $xml);
        $xml = str_replace('<serie>ABC</serie>', '', $xml);
        $xml = str_replace('<dCompet>2026-05-01</dCompet>', '<dCompet>2027-01-01</dCompet>', $xml);
        $xml = str_replace('<tribISSQN>1</tribISSQN>', '<tribISSQN>4</tribISSQN>', $xml);

        $danfse = new Danfse($xml);

        $text = $this->textFromPdf($danfse->render());

        $this->assertStringContainsString('TOMADOR/ADQUIRENTE DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', $text);
        $this->assertStringContainsString('DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', $text);
        $this->assertStringContainsString('INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', $text);
        $this->assertStringContainsString('TRIBUTAÇÃO MUNICIPAL (ISSQN) - OPERAÇÃO NÃO SUJEITA AO ISSQN', $text);
        $this->assertStringContainsString('SÉRIE DA DPS -', $text);
        $this->assertStringNotContainsString('TRIBUTAÇÃO FEDERAL (EXCETO CBS)', $text);
    }

    /**
     * @dataProvider participantesNaoIdentificadosProvider
     */
    public function testInformaParticipanteNaoIdentificadoConformeNt008(
        string $tagPattern,
        string $expectedMessage,
        string $presentBlock
    ): void {
        $xml = preg_replace(
            $tagPattern,
            '',
            file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')
        );

        $text = $this->textFromPdf((new Danfse($xml))->render());

        $this->assertStringContainsString($expectedMessage, $text);
        $this->assertStringContainsString($presentBlock, $text);
    }

    public function testInformaDestinatarioIgualAoTomadorConformeNt008(): void
    {
        $xml = str_replace(
            '<CNPJ>99888777000166</CNPJ>',
            '<CPF>12345678901</CPF>',
            file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')
        );

        $text = $this->textFromPdf((new Danfse($xml))->render());

        $this->assertStringContainsString(
            'O DESTINATÁRIO É O PRÓPRIO TOMADOR/ADQUIRENTE DA OPERAÇÃO',
            $text
        );
        $this->assertStringContainsString('TOMADOR / ADQUIRENTE', $text);
        $this->assertStringContainsString('INTERMEDIÁRIO DA OPERAÇÃO', $text);
    }

    public function testDestinatarioIgualAoTomadorHomologacaoESubstituicao(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml');
        $xml = str_replace('<tpAmb>1</tpAmb>', '<tpAmb>2</tpAmb>', $xml);
        $xml = str_replace('<cStat>100</cStat>', '<cStat>102</cStat>', $xml);
        $xml = str_replace('<CNPJ>99888777000166</CNPJ>', '<CPF>12345678901</CPF>', $xml);
        $xml = str_replace(
            '<serv>',
            '<subst><chSubstda>99999999999999999999999999999999999999999999999999</chSubstda></subst><serv>',
            $xml
        );

        $text = $this->textFromPdf((new Danfse($xml))->render());

        $this->assertStringContainsString('NFS-e SEM VALIDADE JURÍDICA', $text);
        $this->assertStringContainsString('O DESTINATÁRIO É O PRÓPRIO TOMADOR/ADQUIRENTE DA OPERAÇÃO', $text);
        $this->assertStringContainsString('NFS-e Subst.: 99999999999999999999999999999999999999999999999999', $text);
        $this->assertStringContainsString('NFS-e de Decisão Judicial', $text);
        $this->assertStringNotContainsString('SUBSTITUÍDA', $text);
    }

    public function testNaoImprimeMarcaDaguaCanceladaParaCStat101(): void
    {
        $xml = str_replace(
            '<cStat>100</cStat>',
            '<cStat>101</cStat>',
            file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')
        );

        $pdf = (new Danfse($xml))->render();
        $text = $this->textFromPdf($pdf);
        $operators = $this->decompressedPdfOperators($pdf);

        $this->assertStringContainsString('NFS-e de Substituição Gerada', $text);
        $this->assertStringNotContainsString('CANCELADA', $text);
        $this->assertEquals(0.0, $this->watermarkFontSize($operators, 'CANCELADA'));
    }

    public function testNaoImprimeMarcaDaguaSubstituidaParaCStat102(): void
    {
        $xml = str_replace(
            '<cStat>100</cStat>',
            '<cStat>102</cStat>',
            file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')
        );
        $xml = str_replace(
            '<serv>',
            '<subst><chSubstda>99999999999999999999999999999999999999999999999999</chSubstda></subst><serv>',
            $xml
        );

        $pdf = (new Danfse($xml))->render();
        $text = $this->textFromPdf($pdf);
        $operators = $this->decompressedPdfOperators($pdf);

        $this->assertStringContainsString('NFS-e de Decisão Judicial', $text);
        $this->assertStringContainsString('NFS-e Subst.: 99999999999999999999999999999999999999999999999999', $text);
        $this->assertStringNotContainsString('SUBSTITUÍDA', $text);
        $this->assertEquals(0.0, $this->watermarkFontSize($operators, 'SUBSTITUÍDA'));
    }

    public function testImprimeMarcaDaguaCanceladaQuandoMarcadaManualmente(): void
    {
        $danfse = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $danfse->setAsCanceled();

        $pdf = $danfse->render();
        $text = $this->textFromPdf($pdf);
        $operators = $this->decompressedPdfOperators($pdf);

        $this->assertStringContainsString('CANCELADA', $text);
        $this->assertStringContainsString('0.651 0.651 0.651 rg', $operators);
        $this->assertGreaterThan(50.0, $this->watermarkFontSize($operators, 'CANCELADA'));
    }

    public function testImprimeMarcaDaguaSubstituidaQuandoMarcadaManualmente(): void
    {
        $danfse = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $danfse->setAsSubstituted();

        $pdf = $danfse->render();
        $text = $this->textFromPdf($pdf);
        $operators = $this->decompressedPdfOperators($pdf);

        $this->assertStringContainsString('SUBSTITUÍDA', $text);
        $this->assertStringContainsString('0.651 0.651 0.651 rg', $operators);
        $this->assertGreaterThan(50.0, $this->watermarkFontSize($operators, 'SUBSTITUÍDA'));
    }

    public function testRenderizaXmlsReaisSanitizadosEmPaginaUnica(): void
    {
        $files = glob(TEST_FIXTURES . 'xml/nfse-real/*.xml');

        $this->assertCount(2, $files);

        foreach ($files as $file) {
            $xml = file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression(
                '/<Signature|<X509Certificate|<SignatureValue|<DigestValue/i',
                $xml,
                basename($file)
            );
            $this->assertDoesNotMatchRegularExpression(
                '/POLICLINICA|RECAPAGEM|MARIVAL|AGROSUL|NPR|SAF[E]?WEB|SYN?GULAR|@[a-z0-9.-]+\.com\.br/i',
                $xml,
                basename($file)
            );

            $pdf = (new Danfse($xml))->render();
            $parser = new Parser();
            $document = $parser->parseContent($pdf);
            $text = $this->normalize($document->getText());

            $this->assertCount(1, $document->getPages(), basename($file));
            $this->assertStringContainsString('DANFSe v2.0', $text, basename($file));
            $this->assertStringContainsString('Documento Auxiliar da NFS-e', $text, basename($file));
            $this->assertMatchesRegularExpression('/\b\d{50}\b/', $text, basename($file));
            $this->assertStringContainsString('INFORMAÇÕES COMPLEMENTARES', $text, basename($file));
            $this->assertStringContainsString('Empresa Exemplo Publico', $text, basename($file));
            $this->assertStringContainsString('example.org', $text, basename($file));
        }
    }

    public function testPermiteSuprimirCanhotoOpcionalMantendoPaginaUnica(): void
    {
        $danfse = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $danfse->setPrintCanhoto(false);

        $parser = new Parser();
        $document = $parser->parseContent($danfse->render());
        $text = $this->normalize($document->getText());

        $this->assertCount(1, $document->getPages());
        $this->assertStringContainsString('INFORMAÇÕES COMPLEMENTARES', $text);
        $this->assertStringNotContainsString('Data Cientificação', $text);
        $this->assertStringNotContainsString('Identificação e Assinatura', $text);
    }

    public function testPermiteImprimirLinhaPontilhadaOpcionalNoCanhoto(): void
    {
        $default = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $withCutLine = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $withCutLine->setPrintCanhotoCutLine(true);

        $defaultLineCount = $this->dashedLineCount($default->render());
        $cutLineCount = $this->dashedLineCount($withCutLine->render());

        $this->assertGreaterThan(0, $defaultLineCount);
        $this->assertGreaterThan($defaultLineCount, $cutLineCount);
    }

    public function testNaoImprimeRodapeDeIntegradorPorPadrao(): void
    {
        $text = $this->textFromPdf(
            (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render()
        );

        $this->assertStringNotContainsString('Impresso em', $text);
        $this->assertStringNotContainsString('Powered by NFePHP', $text);
    }

    public function testPermiteRodapeOpcionalDeIntegrador(): void
    {
        $danfse = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $danfse->creditsIntegratorFooter('Integrador Exemplo', true);

        $text = $this->textFromPdf($danfse->render());

        $this->assertMatchesRegularExpression('/Impresso em \d{2}\/\d{2}\/\d{4} as \d{2}:\d{2}:\d{2}/', $text);
        $this->assertStringContainsString('Integrador Exemplo', $text);
        $this->assertStringContainsString('Powered by NFePHP', $text);
    }

    public function testPermiteRodapeOpcionalComNomeCustomizadoSemPowered(): void
    {
        $danfse = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $danfse->creditsIntegratorFooter('Camu Tecnologia Fiscal', false);

        $text = $this->textFromPdf($danfse->render());

        $this->assertStringContainsString('Camu Tecnologia Fiscal', $text);
        $this->assertStringContainsString('Impresso em', $text);
        $this->assertStringNotContainsString('Powered by NFePHP', $text);
    }

    public function testRenderizaComLogoPngInformada(): void
    {
        $logo = $this->createTemporaryPngLogo();
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render($logo);
        $text = $this->textFromPdf($pdf);

        $this->assertStringContainsString('DANFSe v2.0', $text);
        $this->assertStringContainsString('Documento Auxiliar da NFS-e', $text);
    }

    public function testUsaLogoOficialConvertidaComoPadrao(): void
    {
        $logo = dirname(__DIR__, 2) . '/src/NFSe/assets/logo-nfse.jpg';
        $info = getimagesize($logo);

        $this->assertIsArray($info);
        $this->assertSame(IMAGETYPE_JPEG, $info[2]);

        $text = $this->textFromPdf(
            (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render()
        );

        $this->assertStringContainsString('DANFSe v2.0', $text);
        $this->assertStringNotContainsString('NFSe Nota Fiscal de Serviço Eletrônica', $text);
    }

    public function testUsaLayoutAbertoSemCaixasIndividuaisEmExcesso(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);

        preg_match_all('/\bre\s+S\b/', $operators, $strokedRectangles);
        preg_match_all('/\bm\s+[0-9.]+\s+[0-9.]+\s+l\s+S\b/', $operators, $singleRules);

        $this->assertLessThanOrEqual(25, count($strokedRectangles[0]));
        $this->assertLessThanOrEqual(28, count($singleRules[0]));
        $this->assertGreaterThan(12, count($singleRules[0]));
    }

    public function testValoresMonetariosSeguemAlinhamentoEsquerdoDoModeloOficial(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);

        $moneyPosition = $this->firstTextPosition($operators, 'R$ 990,00');
        $percentPosition = $this->firstTextPosition($operators, '5,00%');

        $this->assertNotNull($moneyPosition);
        $this->assertNotNull($percentPosition);
        $this->assertLessThan(7.0, $moneyPosition[0]);
        $this->assertGreaterThan(53.0, $percentPosition[0]);
        $this->assertLessThan(58.0, $percentPosition[0]);
    }

    public function testRenderizaFundosCinzaObrigatoriosPorPadraoConformeNt008(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);

        preg_match_all('/0\.949 0\.949 0\.949 rg\s+[0-9.\s-]+re f/', $operators, $grayFills);

        $this->assertGreaterThanOrEqual(10, count($grayFills[0]));
    }

    public function testChaveDeAcessoNaoUsaFundoCinzaConformeNt008(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);

        preg_match_all('/0\.949 0\.949 0\.949 rg\s+([0-9.\s-]+)re f/', $operators, $matches);

        foreach ($matches[1] as $rectangle) {
            $parts = preg_split('/\s+/', trim($rectangle));
            if (count($parts) !== 4) {
                continue;
            }
            $box = $this->pdfRectToMillimeters(array_merge([''], $parts));

            $this->assertNotEquals([2.0, 14.8, 154.0, 7.7], $box);
        }
    }

    public function testEspessurasDeLinhasSeguemNt008(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);

        $this->assertStringContainsString('1.00 w', $operators);
        $this->assertStringContainsString('0.50 w', $operators);
    }

    public function testCorpoImpressoUsaMargemMaximaDeDoisMilimetrosConformeNt008(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);
        preg_match_all(
            '/([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)\s+(-?[0-9.]+)\s+re\s+[fDS]/',
            $operators,
            $rectangles,
            PREG_SET_ORDER
        );

        $bodyRectangles = [];
        foreach ($rectangles as $rectangle) {
            $box = $this->pdfRectToMillimeters($rectangle);
            if ($box[0] >= 1.95 && $box[0] <= 2.05) {
                $bodyRectangles[] = $box;
            }
        }

        $this->assertNotEmpty($bodyRectangles);
        $this->assertContainsEquals([2.0, 3.0, 206.0, 11.6], $bodyRectangles);
    }

    public function testPermiteDesligarFundosCinzaQuandoNecessarioParaImpressao(): void
    {
        $danfse = new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml'));
        $danfse->setPrintBackgrounds(false);

        $operators = $this->decompressedPdfOperators($danfse->render());
        preg_match_all('/0\.949 0\.949 0\.949 rg\s+[0-9.\s-]+re f/', $operators, $grayFills);

        $this->assertCount(0, $grayFills[0]);
    }

    public function testRegrasDoBlocoIdentificacaoNaoAtravessamAreaDoQrCode(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);
        preg_match_all(
            '/([0-9.]+)\s+([0-9.]+)\s+m\s+([0-9.]+)\s+([0-9.]+)\s+l\s+S/',
            $operators,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $line) {
            [$x1, $y1, $x2, $y2] = $this->pdfLineToMillimeters($line);
            $isHorizontal = abs($y1 - $y2) < 0.05;
            $overlapsQrX = max($x1, $x2) >= 174.8 && min($x1, $x2) <= 190.0;
            $crossesQrY = $y1 >= 16.7 && $y1 <= 31.9;

            $this->assertFalse($isHorizontal && $overlapsQrX && $crossesQrY);
        }
    }

    public function testTextosAuxiliaresDoCabecalhoEQRCodeUsamTamanhosDaNt008(): void
    {
        $pdf = (new Danfse(file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')))->render();
        $operators = $this->decompressedPdfOperators($pdf);

        $this->assertSame(
            6.0,
            $this->textFontSize($operators, 'Ambiente gerador: 1')
        );
        $this->assertSame(
            6.0,
            $this->textFontSize($operators, 'Ambiente: Produção')
        );
        $this->assertSame(
            6.0,
            $this->textFontSize($operators, 'A autenticidade desta NFS-e pode ser verificada')
        );

        $qrTextLastLinePosition = $this->firstTextPosition(
            $operators,
            'chave de acesso no portal nacional da NFS-e'
        );

        $this->assertNotNull($qrTextLastLinePosition);
        $this->assertLessThan(43.0, $qrTextLastLinePosition[1]);
    }

    public function testOmiteMunicipioDoCabecalhoQuandoCodigoTributacaoNacionalFor99(): void
    {
        $xml = str_replace(
            '<cTribNac>010101</cTribNac>',
            '<cTribNac>990101</cTribNac>',
            file_get_contents(TEST_FIXTURES . 'xml/nfse-v2.xml')
        );

        $text = $this->textFromPdf((new Danfse($xml))->render());

        $this->assertStringNotContainsString('Município: Curitiba / PR', $text);
        $this->assertStringContainsString('Ambiente gerador: 1', $text);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public function participantesNaoIdentificadosProvider(): array
    {
        return [
            'tomador/adquirente ausente' => [
                '/\s*<toma>.*?<\/toma>/s',
                'TOMADOR/ADQUIRENTE DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e',
                'DESTINATÁRIO DA OPERAÇÃO',
            ],
            'destinatario ausente' => [
                '/\s*<dest>.*?<\/dest>/s',
                'DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e',
                'TOMADOR / ADQUIRENTE',
            ],
            'intermediario ausente' => [
                '/\s*<interm>.*?<\/interm>/s',
                'INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e',
                'DESTINATÁRIO DA OPERAÇÃO',
            ],
        ];
    }

    private function normalize(string $text): string
    {
        return preg_replace('/\s+/u', ' ', $text);
    }

    private function textFromPdf(string $pdf): string
    {
        $parser = new Parser();
        return $this->normalize($parser->parseContent($pdf)->getText());
    }

    private function createTemporaryPngLogo(): string
    {
        $path = sys_get_temp_dir() . '/nfse-test-logo.png';
        $image = imagecreatetruecolor(360, 90);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        $green = imagecolorallocate($image, 89, 145, 94);
        $blue = imagecolorallocate($image, 58, 72, 142);
        imagestring($image, 5, 8, 20, 'NFS', $green);
        imagestring($image, 5, 95, 28, 'e', $blue);
        imagestring($image, 3, 130, 20, 'Nota Fiscal de', $blue);
        imagestring($image, 3, 130, 42, 'Servico Eletronica', $blue);
        imagepng($image, $path);

        return $path;
    }

    private function decompressedPdfOperators(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches);

        $operators = '';
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if ($decoded === false) {
                $decoded = @gzinflate(substr($stream, 2));
            }
            if ($decoded === false) {
                $decoded = @gzdecode($stream);
            }
            if ($decoded !== false) {
                if (strpos($decoded, ' BT ') === false && strpos($decoded, ' re ') === false) {
                    continue;
                }
                $operators .= $decoded . "\n";
            }
        }

        return $operators;
    }

    private function dashedLineCount(string $pdf): int
    {
        preg_match_all(
            '/\bm\s+[0-9.]+\s+[0-9.]+\s+l\s+S\b/',
            $this->decompressedPdfOperators($pdf),
            $lines
        );

        return count($lines[0]);
    }

    /**
     * @param array<int, string> $line
     * @return array{float, float, float, float}
     */
    private function pdfLineToMillimeters(array $line): array
    {
        $scale = 72 / 25.4;
        $pageHeight = 297.0;

        return [
            ((float) $line[1]) / $scale,
            $pageHeight - (((float) $line[2]) / $scale),
            ((float) $line[3]) / $scale,
            $pageHeight - (((float) $line[4]) / $scale),
        ];
    }

    /**
     * @param array<int, string> $rect
     * @return array{float, float, float, float}
     */
    private function pdfRectToMillimeters(array $rect): array
    {
        $scale = 72 / 25.4;
        $pageHeight = 297.0;

        return [
            round(((float) $rect[1]) / $scale, 1),
            round($pageHeight - (((float) $rect[2]) / $scale), 1),
            round(((float) $rect[3]) / $scale, 1),
            round(abs(((float) $rect[4]) / $scale), 1),
        ];
    }

    /**
     * @return array{float, float}|null
     */
    private function firstTextPosition(string $operators, string $text): ?array
    {
        $escaped = preg_quote(iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text), '/');
        if (!preg_match('/BT\s+([0-9.]+)\s+([0-9.]+)\s+Td\s+\(' . $escaped . '\)\s+Tj\s+ET/', $operators, $match)) {
            return null;
        }

        $scale = 72 / 25.4;
        $pageHeight = 297.0;

        return [
            ((float) $match[1]) / $scale,
            $pageHeight - (((float) $match[2]) / $scale),
        ];
    }

    private function watermarkFontSize(string $operators, string $text): float
    {
        return $this->textFontSize($operators, $text);
    }

    private function textFontSize(string $operators, string $text): float
    {
        $encoded = '(' . iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) . ') Tj';
        $textPosition = strpos($operators, $encoded);
        if ($textPosition === false) {
            return 0.0;
        }

        preg_match_all('/BT \/F\d+ ([0-9.]+) Tf ET/', substr($operators, 0, $textPosition), $matches);
        if (empty($matches[1])) {
            return 0.0;
        }

        return (float) end($matches[1]);
    }

    private function minimumFontSize(string $operators): float
    {
        preg_match_all('/BT \/F\d+ ([0-9.]+) Tf ET/', $operators, $matches);
        if (empty($matches[1])) {
            return 0.0;
        }

        return min(array_map('floatval', $matches[1]));
    }
}
