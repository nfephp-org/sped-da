<?php

namespace NFePHP\DA\NFSe;

use Com\Tecnick\Barcode\Barcode;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Exception;
use NFePHP\DA\Common\DaCommon;
use NFePHP\DA\Legacy\Pdf;

// phpcs:disable Generic.Files.LineLength.TooLong
class Danfse extends DaCommon
{
    private const BLOCK_LINE_WIDTH = 0.176;
    private const PAGE_LINE_WIDTH = 0.353;
    private const PAGE_MARGIN = 1.5;
    private const X = 2.0;
    private const WIDTH = 206.0;
    private const CELL = 51.5;
    private const CELL2 = 103.0;
    private const COL2 = 53.5;
    private const COL3 = 105.0;
    private const COL4 = 156.5;
    private const ROW = 6.3;
    private const ROW_TOTAL = 6.7;
    private const TITLE_ROW = 3.9;
    private const GRAY = 242;
    private const LABEL_FONT = 'arial';
    private const CONTENT_FONT = 'arial';

    /** @var string */
    protected $xml;

    /** @var DOMDocument */
    protected $dom;

    /** @var DOMXPath */
    private $xpath;

    /** @var DOMElement */
    private $infNFSe;

    /** @var DOMElement */
    private $infDPS;

    /** @var bool */
    private $printCanhoto = true;

    /** @var bool */
    private $printCanhotoCutLine = false;

    /** @var bool */
    private $canceled = false;

    /** @var bool */
    private $substituted = false;

    /** @var bool */
    private $printBackgrounds = true;

    /** @var bool */
    private $printFooter = false;

    /** @var array<string, bool> */
    private $drawnRules = [];

    /** @var float */
    private $fieldRuleLeft = self::X;

    /** @var float */
    private $fieldRuleRight = self::X + self::WIDTH;

    public function __construct($xml)
    {
        $this->xml = $xml;
        if (empty($xml)) {
            throw new Exception('Um xml de NFS-e deve ser passado ao construtor da classe.');
        }
        $this->loadXml();
    }

    public function setPrintCanhoto($flag = true)
    {
        $this->printCanhoto = (bool) $flag;
    }

    public function setPrintCanhotoCutLine($flag = true)
    {
        $this->printCanhotoCutLine = (bool) $flag;
    }

    public function setAsCanceled()
    {
        $this->canceled = true;
    }

    public function setAsSubstituted()
    {
        $this->substituted = true;
    }

    public function setPrintBackgrounds($flag = true)
    {
        $this->printBackgrounds = (bool) $flag;
    }

    public function creditsIntegratorFooter($message = '', $powered = true)
    {
        parent::creditsIntegratorFooter($message, $powered);
        $this->printFooter = true;
    }

    public function render($logo = '')
    {
        $this->monta($logo);
        return $this->pdf->getPdf();
    }

    protected function monta($logo = '')
    {
        $this->orientacao = 'P';
        $this->papel = 'A4';
        $this->maxW = 210;
        $this->maxH = 297;
        $this->margsup = self::PAGE_MARGIN;
        $this->margesq = self::PAGE_MARGIN;
        $this->marginf = self::PAGE_MARGIN;
        $this->wPrint = $this->maxW - ($this->margesq * 2);
        $this->hPrint = $this->maxH - $this->margsup - $this->marginf;

        if (!empty($logo)) {
            $this->logomarca = $this->prepareLogo($logo);
        }

        $this->pdf = new Pdf($this->orientacao, 'mm', $this->papel);
        $this->pdf->aliasNbPages();
        $this->pdf->setMargins(self::PAGE_MARGIN, self::PAGE_MARGIN);
        $this->pdf->setAutoPageBreak(false);
        $this->pdf->open();
        $this->pdf->addPage($this->orientacao, $this->papel);
        $this->pdf->setTextColor(0, 0, 0);
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setFillColor(255, 255, 255);
        $this->drawnRules = [];

        $this->drawPageBorder();
        $this->drawHeader($logo);
        $this->drawIdentification();

        $y = 43.4;
        $prest = $this->childNode('prest', $this->infDPS);
        $toma = $this->childNode('toma', $this->infDPS);
        $ibscbs = $this->childNode('IBSCBS', $this->infDPS);
        $dest = $this->childNode('dest', $ibscbs);
        $interm = $this->childNode('interm', $this->infDPS);

        $y = $this->drawPersonBlock('PRESTADOR / FORNECEDOR', $prest, $y, true, '', true);
        $y = $this->drawPersonBlock(
            'TOMADOR / ADQUIRENTE',
            $toma,
            $y,
            true,
            'TOMADOR/ADQUIRENTE DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e'
        );
        $y = $this->drawDestinatarioBlock($dest, $toma, $y);
        $y = $this->drawPersonBlock(
            'INTERMEDIÁRIO DA OPERAÇÃO',
            $interm,
            $y,
            true,
            'INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e'
        );
        $y = $this->drawServico($y);
        $y = $this->drawIssqn($y, $prest);
        $y = $this->drawFederal($y);
        $y = $this->drawIbsCbs($y);
        $y = $this->drawTotals($y);
        $y = $this->drawInformacoesComplementares($y);

        if ($this->printCanhoto) {
            $this->drawCanhoto(max($y, 281.0));
        }

        $this->drawFooter();
        $this->drawWatermark();
    }

    private function loadXml()
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->formatOutput = false;
        if (!$this->dom->loadXML($this->xml)) {
            throw new Exception('O xml de NFS-e informado é inválido.');
        }
        $this->xpath = new DOMXPath($this->dom);
        $this->infNFSe = $this->firstNode('infNFSe');
        $this->infDPS = $this->firstNode('infDPS', $this->infNFSe);
        if (!$this->infNFSe || !$this->infDPS) {
            throw new Exception('O xml informado não contém as tags infNFSe/infDPS da NFS-e.');
        }
    }

    private function drawPageBorder()
    {
        $this->pdf->setLineWidth(self::PAGE_LINE_WIDTH);
        $this->pdf->rect(self::PAGE_MARGIN, self::PAGE_MARGIN, 207.0, 294.0, 'D');
        $this->pdf->setLineWidth(self::BLOCK_LINE_WIDTH);
    }

    private function drawHeader($logo = '')
    {
        $this->fillRect(self::X, 3.0, self::WIDTH, 11.6);
        $this->strokeRect(self::X, 3.0, self::WIDTH, 11.6);

        if (empty($this->logomarca) && is_file($this->defaultLogoPath())) {
            $this->logomarca = $this->defaultLogoPath();
        }
        if (!empty($this->logomarca)) {
            $this->drawLogoImage();
        } else {
            $this->drawDefaultLogo();
        }

        $this->text(self::COL2, 4.2, self::CELL2, 4.0, 'DANFSe v2.0', $this->fontLabel(9, 'B'), 'C', 'C');
        $this->text(self::COL2, 8.0, self::CELL2, 3.6, 'Documento Auxiliar da NFS-e', $this->fontLabel(9, 'B'), 'C', 'C');
        if ($this->value('tpAmb', $this->infDPS) === '2') {
            $this->pdf->setTextColor(255, 0, 0);
            $this->text(
                self::COL2,
                11.2,
                self::CELL2,
                3.0,
                'NFS-e SEM VALIDADE JURÍDICA',
                $this->fontLabel(9, 'B'),
                'C',
                'C'
            );
            $this->pdf->setTextColor(0, 0, 0);
        }

        $municipio = $this->joinNonEmpty([
            $this->value('xLocEmi', $this->infNFSe),
            $this->value('UF', $this->firstNode('enderNac', $this->firstNode('emit', $this->infNFSe)))
        ], ' / ');
        if (!empty($municipio) && substr($this->value('cTribNac', $this->infDPS), 0, 2) !== '99') {
            $municipio = 'Município: ' . $municipio;
        } else {
            $municipio = '';
        }
        $this->text(self::COL4, 3.5, self::CELL, 3.4, $this->dash($municipio), $this->fontContent(8), 'C', 'C');
        $this->text(self::COL4, 8.7, self::CELL, 2.7, 'Ambiente gerador: ' . $this->dash($this->value('ambGer', $this->infNFSe)), $this->fontContent(6));
        $this->text(self::COL4, 11.3, self::CELL, 2.7, 'Ambiente: ' . $this->tpAmb($this->value('tpAmb', $this->infDPS)), $this->fontContent(6));
    }

    private function drawIdentification()
    {
        $this->strokeRect(self::X, 14.8, self::WIDTH, 28.4);

        $this->fieldRuleRight = 156.0;
        $this->drawField('CHAVE DE ACESSO DA NFS-E', $this->accessKey(), self::X, 14.8, 154.0, 7.7, false, 7);
        $this->drawQrCode();
        $this->drawField('NÚMERO DA NFS-e', $this->value('nNFSe', $this->infNFSe), self::X, 22.7, self::CELL, self::ROW_TOTAL, false, 7);
        $this->drawField('COMPETÊNCIA DA NFS-e', $this->formatDate($this->value('dCompet', $this->infDPS)), self::COL2, 22.7, self::CELL, self::ROW_TOTAL, false, 7);
        $this->drawField('DATA E HORA DA EMISSÃO DA NFS-E', $this->formatDateTime($this->value('dhProc', $this->infNFSe)), self::COL3, 22.7, self::CELL, self::ROW_TOTAL, false, 7);
        $this->drawField('NÚMERO DA DPS', $this->value('nDPS', $this->infDPS), self::X, 29.6, self::CELL, self::ROW_TOTAL, false, 7);
        $this->drawField('SÉRIE DA DPS', $this->value('serie', $this->infDPS), self::COL2, 29.6, self::CELL, self::ROW_TOTAL, false, 7);
        $this->drawField('DATA E HORA DA EMISSÃO DA DPS', $this->formatDateTime($this->value('dhEmi', $this->infDPS)), self::COL3, 29.6, self::CELL, self::ROW_TOTAL, false, 7);
        $this->drawField('EMITENTE DA NFS-E', $this->tpEmit($this->value('tpEmit', $this->infDPS)), self::X, 36.5, self::CELL, self::ROW_TOTAL, true, 7);
        $this->drawField('SITUAÇÃO DA NFS-E', $this->situacao($this->value('cStat', $this->infNFSe)), self::COL2, 36.5, self::CELL, self::ROW_TOTAL, false, 7);
        $this->drawField('FINALIDADE', $this->finalidade($this->value('finNFSe', $this->infDPS)), self::COL3, 36.5, self::CELL, self::ROW_TOTAL, false, 7);
        $this->fieldRuleRight = self::X + self::WIDTH;
    }

    private function drawQrCode()
    {
        $url = 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=' . $this->accessKey();
        $barcode = new Barcode();
        $bobj = $barcode->getBarcodeObj('QRCODE,M', $url, -4, -4, 'black', [-2, -2, -2, -2])
            ->setBackgroundColor('white');
        $pic = 'data://text/plain;base64,' . base64_encode($bobj->getPngData());
        $this->pdf->image($pic, 174.8, 16.7, 15.2, 15.2, 'PNG');
        $this->text(
            158.0,
            32.0,
            47.2,
            10.0,
            'A autenticidade desta NFS-e pode ser verificada pela leitura deste código QR ou pela consulta da chave de acesso no portal nacional da NFS-e',
            $this->fontContent(6),
            'T',
            'C',
            false
        );
    }

    private function drawDefaultLogo()
    {
        $this->pdf->setTextColor(85, 143, 89);
        $this->text(5.0, 4.0, 22.0, 7.2, 'NFS', $this->fontLabel(18, 'B'), 'C', 'R');
        $this->pdf->setTextColor(58, 72, 142);
        $this->text(27.0, 5.0, 7.0, 6.2, 'e', $this->fontLabel(16, 'B'), 'C', 'L');
        $this->pdf->setTextColor(100, 100, 120);
        $this->text(33.0, 5.0, 13.0, 3.0, 'Nota Fiscal de', $this->fontContent(5), 'C', 'L');
        $this->text(33.0, 8.0, 13.0, 3.0, 'Serviço Eletrônica', $this->fontContent(5), 'C', 'L');
        $this->pdf->setTextColor(0, 0, 0);
    }

    private function drawLogoImage()
    {
        $maxW = 40.0;
        $maxH = 8.5;
        $x = 4.9;
        $y = 4.4;
        $info = getimagesize($this->logomarca);
        if (!$info || empty($info[0]) || empty($info[1])) {
            return;
        }
        $scale = min($maxW / $info[0], $maxH / $info[1]);
        $w = $info[0] * $scale;
        $h = $info[1] * $scale;
        $this->pdf->image(
            $this->logomarca,
            $x + (($maxW - $w) / 2),
            $y + (($maxH - $h) / 2),
            $w,
            $h,
            $this->logoType($this->logomarca)
        );
    }

    private function prepareLogo($logo)
    {
        if (substr($logo, 0, 24) === 'data://text/plain;base64') {
            if ($this->logoType($logo) === 'PNG') {
                return $this->pngLogoToJpegDataUrl($logo);
            }
            return $logo;
        }
        if (is_file($logo)) {
            if ($this->logoType($logo) === 'PNG') {
                return $this->pngLogoToJpegDataUrl($logo);
            }
            return $logo;
        }
        return $this->adjustImage($logo, false);
    }

    private function defaultLogoPath()
    {
        return __DIR__ . '/assets/logo-nfse.jpg';
    }

    private function logoType($logo)
    {
        $info = getimagesize($logo);
        if (!$info) {
            return '';
        }
        if ($info[2] === IMAGETYPE_PNG) {
            return 'PNG';
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            return 'JPEG';
        }
        return '';
    }

    private function pngLogoToJpegDataUrl($logo)
    {
        $source = imagecreatefrompng($logo);
        if (!$source) {
            return $logo;
        }
        $width = imagesx($source);
        $height = imagesy($source);
        $target = imagecreatetruecolor($width, $height);
        $backgroundTone = $this->printBackgrounds ? self::GRAY : 255;
        $background = imagecolorallocate($target, $backgroundTone, $backgroundTone, $backgroundTone);
        imagefill($target, 0, 0, $background);
        imagecopy($target, $source, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($target, null, 100);
        $jpeg = ob_get_clean();

        return 'data://text/plain;base64,' . base64_encode($jpeg);
    }

    private function drawPersonBlock($title, ?DOMElement $node, $y, $withIm, $missingMessage = '', $required = false)
    {
        if (!$node || (!$required && !$this->personIdentified($node))) {
            return $this->drawMessageBlock($missingMessage, $y);
        }

        $end = $this->childNode('end', $node);
        $this->drawSectionTitle($title, self::X, $y, self::CELL, self::ROW);
        $this->drawField('CNPJ / CPF / NIF', $this->document($node), self::COL2, $y, self::CELL, self::ROW);
        if ($withIm) {
            $this->drawField('Indicador Municipal (Inscrição)', $this->value('IM', $node), self::COL3, $y, self::CELL, self::ROW);
        }
        $this->drawField('Telefone', $this->formatPhone($this->value('fone', $node)), self::COL4, $y, self::CELL, self::ROW);

        $this->drawField('Nome / Nome Empresarial', $this->ellipsis($this->value('xNome', $node), 80), self::X, $y + 6.4, self::CELL2, self::ROW);
        $this->drawField('Município / Sigla UF', $this->municipioUf($end), self::COL3, $y + 6.4, self::CELL, self::ROW);
        $this->drawField('Código IBGE / CEP', $this->ibgeCep($end), self::COL4, $y + 6.4, self::CELL, self::ROW);

        $this->drawField('Endereço', $this->ellipsis($this->address($end), 80), self::X, $y + 12.9, self::CELL2, self::ROW);
        $this->drawField('E-mail', $this->value('email', $node), self::COL3, $y + 12.9, self::CELL2, self::ROW);

        if ($title === 'PRESTADOR / FORNECEDOR') {
            $reg = $this->childNode('regTrib', $node);
            $this->drawField('Simples Nacional na Data de Competência', $this->opSimpNac($this->value('opSimpNac', $reg)), self::X, $y + 19.4, self::CELL, self::ROW);
            $this->drawField('Regime de Apuração Tributária pelo SN', $this->regApTribSN($this->value('regApTribSN', $reg)), self::COL3, $y + 19.4, self::CELL2, self::ROW);
            return $y + 25.8;
        }

        return $y + 19.4;
    }

    private function drawDestinatarioBlock(?DOMElement $dest, ?DOMElement $toma, $y)
    {
        if (!$dest || !$this->personIdentified($dest)) {
            return $this->drawMessageBlock('DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e', $y);
        }
        if ($toma && $this->document($dest) !== '-' && $this->document($dest) === $this->document($toma)) {
            return $this->drawMessageBlock('O DESTINATÁRIO É O PRÓPRIO TOMADOR/ADQUIRENTE DA OPERAÇÃO', $y);
        }
        return $this->drawPersonBlock('DESTINATÁRIO DA OPERAÇÃO', $dest, $y, false, '');
    }

    private function drawServico($y)
    {
        $serv = $this->childNode('serv', $this->infDPS);
        $cServ = $this->childNode('cServ', $serv);
        $loc = $this->childNode('locPrest', $serv);
        $codigos = $this->joinNonEmpty([$this->value('cTribNac', $cServ), $this->value('cTribMun', $cServ)], ' / ');
        $descCodigo = $this->value('xTribMun', $cServ);
        if (empty($descCodigo)) {
            $descCodigo = $this->value('xTribNac', $cServ);
        }

        $this->drawSectionTitle('SERVIÇO PRESTADO', self::X, $y, self::CELL, self::ROW);
        $this->drawField('Código de Tributação Nacional / Municipal', $codigos, self::COL2, $y, self::CELL, self::ROW);
        $this->drawField('Código da NBS', $this->formatNbs($this->value('cNBS', $cServ)), self::COL3, $y, self::CELL, self::ROW);
        $this->drawField('Local da Prestação / Sigla UF / País', $this->localPrestacao($loc), self::COL4, $y, self::CELL, self::ROW);
        $this->drawValueOnly($this->ellipsis($descCodigo, 170), self::X, $y + 6.5, self::WIDTH, 4.0, false);
        $this->text(self::X + 0.8, $y + 10.9, self::WIDTH - 1.6, 2.2, 'Descrição do Serviço', $this->fontLabel(6, 'B'));
        $this->drawValueOnly($this->ellipsis($this->value('xDescServ', $cServ), 1300), self::X, $y + 13.2, self::WIDTH, 10.0, false);

        return $y + 23.4;
    }

    private function drawIssqn($y, ?DOMElement $prest)
    {
        $dpsValores = $this->childNode('valores', $this->infDPS);
        $tribMun = $this->childNode('tribMun', $this->childNode('trib', $dpsValores));
        if (!$tribMun || $this->value('tribISSQN', $tribMun) === '4') {
            return $this->drawMessageBlock('TRIBUTAÇÃO MUNICIPAL (ISSQN) - OPERAÇÃO NÃO SUJEITA AO ISSQN', $y);
        }

        $valores = $this->childNode('valores', $this->infNFSe);
        $regTrib = $this->childNode('regTrib', $prest);
        $exigSusp = $this->childNode('exigSusp', $tribMun);
        $bm = $this->childNode('BM', $tribMun);

        $this->drawSectionTitle('TRIBUTAÇÃO MUNICIPAL (ISSQN)', self::X, $y, self::CELL, self::ROW);
        $this->drawField('Tipo de Tributação do ISSQN', $this->tribIssqn($this->value('tribISSQN', $tribMun)), self::COL2, $y, self::CELL, self::ROW);
        $this->drawField('Município / Sigla UF / País da Incidência do ISSQN', $this->issqnLocal($tribMun), self::COL3, $y, self::CELL2, self::ROW);
        $this->drawField('Regime Especial de Tributação do ISSQN', $this->regEspTrib($this->value('regEspTrib', $regTrib)), self::X, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Tipo de Imunidade do ISSQN', $this->value('tpImunidade', $tribMun), self::COL2, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Suspensão da Exigibilidade do ISSQN', $this->tpSusp($this->value('tpSusp', $exigSusp)), self::COL3, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Número Processo Suspensão', $this->value('nProcesso', $exigSusp), self::COL4, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Benefício Municipal', $this->value('tpBM', $bm), self::X, $y + 13.0, self::CELL, self::ROW);
        $this->drawMoneyField('Cálculo do BM', $this->firstValue(['vCalcBM', 'vRedBCBM'], $bm), self::COL2, $y + 13.0, self::CELL, self::ROW);
        $this->drawMoneyField('Total Deduções/Reduções', $this->firstValue(['vDR', 'vCalcDR'], $dpsValores), self::COL3, $y + 13.0, self::CELL, self::ROW);
        $this->drawMoneyField('Desconto Incondicionado', $this->value('vDescIncond', $dpsValores), self::COL4, $y + 13.0, self::CELL, self::ROW);
        $this->drawMoneyField('BC ISSQN', $this->value('vBC', $valores), self::X, $y + 19.5, self::CELL, self::ROW);
        $this->drawField('Alíquota Aplicada', $this->percent($this->value('pAliqAplic', $valores)), self::COL2, $y + 19.5, self::CELL, self::ROW);
        $this->drawField('Retenção do ISSQN', $this->tpRetIssqn($this->value('tpRetISSQN', $tribMun)), self::COL3, $y + 19.5, self::CELL, self::ROW);
        $this->drawMoneyField('ISSQN Apurado', $this->value('vISSQN', $valores), self::COL4, $y + 19.5, self::CELL, self::ROW);

        return $y + 25.9;
    }

    private function drawFederal($y)
    {
        if (!$this->printFederalTax()) {
            return $y;
        }

        $dpsValores = $this->childNode('valores', $this->infDPS);
        $tribFed = $this->childNode('tribFed', $this->childNode('trib', $dpsValores));
        $pisCofins = $this->childNode('piscofins', $tribFed);
        $this->drawSectionTitle('TRIBUTAÇÃO FEDERAL (EXCETO CBS)', self::X, $y, self::CELL, self::ROW);
        $this->drawMoneyField('IRRF', $this->value('vRetIRRF', $tribFed), self::COL2, $y, self::CELL, self::ROW);
        $this->drawMoneyField('Contribuição Previdenciária - Retida', $this->value('vRetCP', $tribFed), self::COL3, $y, self::CELL, self::ROW);
        $this->drawMoneyField('Contribuições Sociais - Retidas', $this->value('vRetCSLL', $tribFed), self::COL4, $y, self::CELL, self::ROW);
        $this->drawMoneyField('PIS - Débito Apuração Própria', $this->value('vPis', $pisCofins), self::X, $y + 6.5, self::CELL, self::ROW);
        $this->drawMoneyField('COFINS - Débito Apuração Própria', $this->value('vCofins', $pisCofins), self::COL2, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Descrição Contrib. Sociais - Retidas', $this->tpRetPisCofins($this->value('tpRetPisCofins', $pisCofins)), self::COL3, $y + 6.5, self::CELL2, self::ROW);

        return $y + 13.0;
    }

    private function drawIbsCbs($y)
    {
        $dpsIbsCbs = $this->childNode('IBSCBS', $this->infDPS);
        $nfseIbsCbs = $this->childNode('IBSCBS', $this->infNFSe);
        $ibscbs = $nfseIbsCbs ?: $dpsIbsCbs;
        $valores = $this->childNode('valores', $ibscbs);
        $dpsValores = $this->childNode('valores', $dpsIbsCbs);
        $trib = $this->childNode('gIBSCBS', $this->childNode('trib', $dpsValores ?: $valores));
        $uf = $this->childNode('uf', $valores);
        $mun = $this->childNode('mun', $valores);
        $fed = $this->childNode('fed', $valores);
        $tot = $this->childNode('totCIBS', $ibscbs);
        $gIBS = $this->childNode('gIBS', $tot);
        $gCBS = $this->childNode('gCBS', $tot);

        $this->drawSectionTitle('TRIBUTAÇÃO IBS / CBS', self::X, $y, self::CELL, self::ROW);
        $this->drawField('CST / cClassTrib', $this->joinNonEmpty([$this->value('CST', $trib), $this->value('cClassTrib', $trib)], ' / '), self::COL2, $y, self::CELL, self::ROW);
        $this->drawField('Indicador de Operação / Código IBGE Incidência / Município Incidência / Sigla UF', $this->ibsLocal($valores), self::COL3, $y, self::CELL2, self::ROW);
        $this->drawMoneyField('Exclusões e Reduções da Base de Cálculo', $this->ibsExclusoes($valores), self::X, $y + 6.5, self::CELL, self::ROW);
        $this->drawMoneyField('Base de Cálculo Após Exclusões e Reduções', $this->value('vBC', $valores), self::COL2, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Red. Alíquota IBS / Red. Alíquota CBS', $this->joinNonEmpty([$this->percent($this->value('pRedAliqUF', $uf)), $this->percent($this->value('pRedAliqMun', $mun)), $this->percent($this->value('pRedAliqCBS', $fed))], ' / '), self::COL3, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Alíquota - IBS UF / IBS Mun', $this->joinNonEmpty([$this->percent($this->value('pIBSUF', $uf)), $this->percent($this->value('pIBSMun', $mun))], ' / '), self::COL4, $y + 6.5, self::CELL, self::ROW);
        $this->drawField('Alíq. Efetiva Municipal - IBS', $this->percent($this->value('pAliqEfetMun', $mun)), self::X, $y + 13.0, self::CELL, self::ROW);
        $this->drawMoneyField('Valor Apurado Municipal - IBS', $this->value('vIBSMun', $gIBS), self::COL2, $y + 13.0, self::CELL, self::ROW);
        $this->drawField('Alíq. Efetiva Estadual - IBS', $this->percent($this->value('pAliqEfetUF', $uf)), self::COL3, $y + 13.0, self::CELL, self::ROW);
        $this->drawMoneyField('Valor Apurado Estadual - IBS', $this->value('vIBSUF', $gIBS), self::COL4, $y + 13.0, self::CELL, self::ROW);
        $this->drawMoneyField('Valor Total Apurado - IBS', $this->value('vIBSTot', $gIBS), self::X, $y + 19.5, self::CELL, self::ROW);
        $this->drawField('Alíquota - CBS', $this->percent($this->value('pCBS', $fed)), self::COL2, $y + 19.5, self::CELL, self::ROW);
        $this->drawField('Alíquota Efetiva - CBS', $this->percent($this->value('pAliqEfetCBS', $fed)), self::COL3, $y + 19.5, self::CELL, self::ROW);
        $this->drawMoneyField('Valor Total Apurado - CBS', $this->value('vCBS', $gCBS), self::COL4, $y + 19.5, self::CELL, self::ROW);

        return $y + 25.9;
    }

    private function drawTotals($y)
    {
        $dpsValores = $this->childNode('valores', $this->infDPS);
        $infValores = $this->childNode('valores', $this->infNFSe);
        $totCibs = $this->childNode('totCIBS', $this->ibsCbsNode());

        $this->drawSectionTitle('VALOR TOTAL DA NFS-E', self::X, $y, self::CELL, self::ROW_TOTAL);
        $this->drawMoneyField('Valor da Operação / Serviço', $this->value('vServPrest', $dpsValores), self::COL2, $y, self::CELL, self::ROW_TOTAL);
        $this->drawMoneyField('Desconto Incondicionado', $this->value('vDescIncond', $dpsValores), self::COL3, $y, self::CELL, self::ROW_TOTAL);
        $this->drawMoneyField('Desconto Condicionado', $this->value('vDescCond', $dpsValores), self::COL4, $y, self::CELL, self::ROW_TOTAL);
        $this->drawMoneyField('Total das Retenções (ISSQN / Federais)', $this->value('vTotalRet', $infValores), self::X, $y + 6.9, self::CELL, self::ROW_TOTAL);
        $this->drawMoneyField('Valor Líquido da NFS-e', $this->value('vLiq', $infValores), self::COL2, $y + 6.9, self::CELL, self::ROW_TOTAL);
        $this->drawMoneyField('Total do IBS/CBS', $this->sumValues([$this->value('vIBSTot', $totCibs), $this->value('vCBS', $totCibs)]), self::COL3, $y + 6.9, self::CELL, self::ROW_TOTAL);
        $this->drawMoneyField('Valor Líquido da NFS-e + IBS/CBS', $this->value('vTotNF', $totCibs), self::COL4, $y + 6.9, self::CELL, self::ROW_TOTAL, true);

        return $y + 13.8;
    }

    private function drawInformacoesComplementares($y)
    {
        $bottom = $this->printCanhoto ? 281.0 : 293.0;
        if ($this->hasFooter()) {
            $bottom -= 5.0;
        }
        $height = max(18.0, $bottom - $y);
        $this->drawSectionTitle('INFORMAÇÕES COMPLEMENTARES', self::X, $y, self::WIDTH, self::TITLE_ROW);
        $this->drawValueOnly($this->informacoesComplementares(), self::X, $y + self::TITLE_ROW, self::WIDTH, $height - self::TITLE_ROW, false);
        return $y + $height;
    }

    private function drawCanhoto($y)
    {
        if ($this->printCanhotoCutLine) {
            $this->pdf->dashedHLine(self::X, $y - 1.2, self::WIDTH, 0.1, 80);
        }

        $this->drawBoxedField('Data Cientificação', '', self::X, $y, self::CELL, self::ROW_TOTAL);
        $this->drawBoxedField('Identificação e Assinatura', '', self::COL2, $y, self::CELL, self::ROW_TOTAL);
        $this->drawBoxedField('Nº NFS-e / Chave NFS-e', $this->value('nNFSe', $this->infNFSe) . ' / ' . $this->accessKey(), self::COL3, $y, self::CELL2, self::ROW_TOTAL);
    }

    private function drawFooter()
    {
        if (!$this->hasFooter()) {
            return;
        }

        $font = $this->fontContent(6);
        $font['style'] = 'I';
        $y = 292.0;
        $text = 'Impresso em ' . date('d/m/Y') . ' as ' . date('H:i:s') . '  ' . trim((string) $this->creditos);
        $this->text(self::X, $y, self::WIDTH, 2.5, trim($text), $font, 'T', 'L', false);
        $this->text(self::X, $y, self::WIDTH, 2.5, $this->powered ? 'Powered by NFePHP®' : '', $font, 'T', 'R', false);
    }

    private function hasFooter()
    {
        return $this->printFooter;
    }

    private function drawWatermark()
    {
        $cStat = $this->value('cStat', $this->infNFSe);
        if ($this->canceled || in_array($cStat, ['101', '135'], true)) {
            $this->watermark('CANCELADA');
        }
        if ($this->substituted || in_array($cStat, ['102', '151'], true)) {
            $this->watermark('SUBSTITUÍDA');
        }
    }

    private function watermark($text)
    {
        $this->pdf->setTextColor(166, 166, 166);
        $size = strlen($this->toIso($text)) > 9 ? 62 : 68;
        $this->pdf->setFont('arial', '', $size);
        $this->pdf->rotate(45, 40, 200);
        $this->pdf->text(40, 200, $this->toIso($text));
        $this->pdf->rotate(0);
        $this->pdf->setTextColor(0, 0, 0);
    }

    private function drawMessageBlock($message, $y)
    {
        $this->fillRect(self::X, $y, self::WIDTH, self::ROW);
        $this->rule(self::X, $y, self::X + self::WIDTH, $y);
        $this->rule(self::X, $y + self::ROW, self::X + self::WIDTH, $y + self::ROW);
        $this->text(self::X, $y, self::WIDTH, self::ROW, $message, $this->fontLabel(7, 'B'), 'C', 'C');
        return $y + self::ROW;
    }

    private function drawSectionTitle($title, $x, $y, $w, $h)
    {
        $this->fillRect($x, $y, min($w, self::CELL), $h);
        $this->rule(self::X, $y, self::X + self::WIDTH, $y);
        $this->rule($this->fieldRuleLeft, $y + $h, $this->fieldRuleRight, $y + $h);
        $this->text($x + 0.8, $y, $w - 1.6, $h, $title, $this->fontLabel(7, 'B'), 'C', 'L');
    }

    private function drawField($label, $value, $x, $y, $w, $h, $highlight = false, $labelSize = 6, $valueAlign = 'L')
    {
        if ($highlight) {
            $this->fillRect($x, $y, $w, $h);
        }
        $valueFont = $this->fontContent(7);
        $this->text($x + 0.8, $y + 0.35, $w - 1.6, 2.2, $label, $this->fontLabel($labelSize, 'B'), 'T', 'L');
        $this->text($x + 0.8, $y + 2.9, $w - 1.6, $h - 3.0, $this->fitText($this->dash($value), $w - 1.6, $valueFont), $valueFont, 'T', $valueAlign, false);
    }

    private function drawBoxedField($label, $value, $x, $y, $w, $h, $highlight = false, $labelSize = 6, $valueAlign = 'L')
    {
        if ($highlight) {
            $this->fillRect($x, $y, $w, $h);
        }
        $this->strokeRect($x, $y, $w, $h);
        $valueFont = $this->fontContent(7);
        $this->text($x + 0.8, $y + 0.35, $w - 1.6, 2.2, $label, $this->fontLabel($labelSize, 'B'), 'T', 'L');
        $this->text($x + 0.8, $y + 2.9, $w - 1.6, $h - 3.0, $this->fitText($this->dash($value), $w - 1.6, $valueFont), $valueFont, 'T', $valueAlign, false);
    }

    private function drawMoneyField($label, $value, $x, $y, $w, $h, $highlight = false)
    {
        $this->drawField($label, $this->money($value), $x, $y, $w, $h, $highlight);
    }

    private function drawValueOnly($value, $x, $y, $w, $h, $fill = false)
    {
        if ($fill) {
            $this->fillRect($x, $y, $w, $h);
        }
        $this->rule(self::X, $y + $h, self::X + self::WIDTH, $y + $h);
        $this->text($x + 0.8, $y + 0.25, $w - 1.6, $h - 0.5, $this->dash($value), $this->fontContent(7), 'T', 'L', false);
    }

    private function text($x, $y, $w, $h, $text, array $font, $vAlign = 'T', $hAlign = 'L', $force = true)
    {
        $this->pdf->textBox($x, $y, $w, $h, $text, $font, $vAlign, $hAlign, false, '', $force);
    }

    private function strokeRect($x, $y, $w, $h)
    {
        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setLineWidth(self::BLOCK_LINE_WIDTH);
        $this->pdf->rect($x, $y, $w, $h, 'D');
    }

    private function rule($x1, $y1, $x2, $y2)
    {
        $key = implode(':', array_map(
            static function ($value) {
                return number_format((float) $value, 2, '.', '');
            },
            [$x1, $y1, $x2, $y2]
        ));
        if (isset($this->drawnRules[$key])) {
            return;
        }
        $this->drawnRules[$key] = true;

        $this->pdf->setDrawColor(0, 0, 0);
        $this->pdf->setLineWidth(self::BLOCK_LINE_WIDTH);
        $this->pdf->line($x1, $y1, $x2, $y2);
    }

    private function fillRect($x, $y, $w, $h)
    {
        if (!$this->printBackgrounds) {
            return;
        }
        $this->pdf->setFillColor(self::GRAY, self::GRAY, self::GRAY);
        $this->pdf->rect($x, $y, $w, $h, 'F');
        $this->pdf->setFillColor(255, 255, 255);
    }

    private function fontLabel($size, $style = '')
    {
        return ['font' => self::LABEL_FONT, 'size' => $size, 'style' => $style];
    }

    private function fontContent($size)
    {
        return ['font' => self::CONTENT_FONT, 'size' => $size, 'style' => ''];
    }

    private function fitText($value, $width, array $font)
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        $this->pdf->setFont($font['font'], $font['style'], $font['size']);
        if ($this->pdf->getStringWidth($text) <= $width) {
            return $text;
        }

        $length = mb_strlen($text, 'UTF-8');
        while ($length > 3) {
            $candidate = mb_substr($text, 0, $length - 3, 'UTF-8') . '...';
            if ($this->pdf->getStringWidth($candidate) <= $width) {
                return $candidate;
            }
            --$length;
        }

        return '...';
    }

    private function firstNode($name, ?DOMNode $context = null)
    {
        $base = $context ?: $this->dom;
        $nodes = $this->xpath->query('.//*[local-name()="' . $name . '"]', $base);
        if ($nodes && $nodes->length > 0) {
            return $nodes->item(0);
        }
        return null;
    }

    private function childNode($name, ?DOMNode $context = null)
    {
        if (!$context) {
            return null;
        }
        foreach ($context->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                return $child;
            }
        }
        return null;
    }

    private function value($name, ?DOMNode $context = null)
    {
        $node = $this->childNode($name, $context) ?: $this->firstNode($name, $context);
        return $node ? trim($node->nodeValue) : '';
    }

    private function firstValue(array $names, ?DOMNode $context = null)
    {
        foreach ($names as $name) {
            $value = $this->value($name, $context);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private function accessKey()
    {
        $id = $this->infNFSe->getAttribute('id') ?: $this->infNFSe->getAttribute('Id');
        if ($id === '') {
            $id = $this->value('chNFSe', $this->infNFSe);
        }
        if (substr($id, 0, 3) === 'NFS') {
            return substr($id, 3);
        }
        return $id;
    }

    private function document(?DOMElement $node)
    {
        if (!$node) {
            return '-';
        }
        $cnpj = $this->value('CNPJ', $node);
        if (!empty($cnpj)) {
            return $this->formatField($cnpj, '##.###.###/####-##');
        }
        $cpf = $this->value('CPF', $node);
        if (!empty($cpf)) {
            return $this->formatField($cpf, '###.###.###-##');
        }
        $nif = $this->value('NIF', $node);
        return $this->dash($nif);
    }

    private function personIdentified(DOMElement $node)
    {
        return $this->value('CNPJ', $node) !== ''
            || $this->value('CPF', $node) !== ''
            || $this->value('NIF', $node) !== ''
            || $this->value('xNome', $node) !== '';
    }

    private function municipioUf(?DOMElement $end)
    {
        $nac = $this->firstNode('endNac', $end) ?: $this->firstNode('enderNac', $end);
        $ext = $this->firstNode('endExt', $end);
        $base = $nac ?: $ext;
        return $this->dash($this->joinNonEmpty([
            $this->firstValue(['xMun', 'xCidade', 'cMun'], $base),
            $this->value('UF', $base)
        ], ' / '));
    }

    private function ibgeCep(?DOMElement $end)
    {
        $nac = $this->firstNode('endNac', $end) ?: $this->firstNode('enderNac', $end);
        $ext = $this->firstNode('endExt', $end);
        $base = $nac ?: $ext;
        $cep = $this->value('CEP', $base);
        if (!empty($cep) && strlen(preg_replace('/\D/', '', $cep)) === 8) {
            $cep = $this->formatField($cep, '##.###-###');
        }
        return $this->dash($this->joinNonEmpty([
            $this->value('cMun', $base),
            $cep ?: $this->value('cEndPost', $base)
        ], ' / '));
    }

    private function address(?DOMElement $end)
    {
        return $this->dash($this->joinNonEmpty([
            $this->value('xLgr', $end),
            $this->value('nro', $end),
            $this->value('xCpl', $end),
            $this->value('xBairro', $end)
        ], ', '));
    }

    private function localPrestacao(?DOMElement $loc)
    {
        return $this->dash($this->joinNonEmpty([
            $this->firstValue(['xLocPrestacao', 'xLocPrest', 'cLocPrest'], $loc),
            $this->value('UF', $loc),
            $this->value('cPaisPrestacao', $loc)
        ], ' / '));
    }

    private function issqnLocal(?DOMElement $tribMun)
    {
        return $this->dash($this->joinNonEmpty([
            $this->firstValue(['xLocIncid', 'cLocIncid'], $tribMun),
            $this->value('UF', $tribMun),
            $this->value('cPaisResult', $tribMun)
        ], ' / '));
    }

    private function ibsLocal(?DOMElement $valores)
    {
        return $this->dash($this->joinNonEmpty([
            $this->value('cIndOp', $valores),
            $this->value('cLocalidadeIncid', $valores),
            $this->value('xLocalidadeIncid', $valores),
            $this->value('UF', $valores)
        ], ' / '));
    }

    private function ibsCbsNode()
    {
        return $this->childNode('IBSCBS', $this->infNFSe) ?: $this->childNode('IBSCBS', $this->infDPS);
    }

    private function ibsExclusoes(?DOMElement $valores)
    {
        $dpsValores = $this->childNode('valores', $this->infDPS);
        return $this->sumValues([
            $this->value('vDescIncond', $dpsValores),
            $this->value('vCalcReeRepRes', $valores),
            $this->value('vISSQN', $this->childNode('valores', $this->infNFSe)),
            $this->value('vPis', $dpsValores),
            $this->value('vCofins', $dpsValores)
        ]);
    }

    private function informacoesComplementares()
    {
        $serv = $this->childNode('serv', $this->infDPS);
        $info = $this->childNode('infoCompl', $serv);
        $obra = $this->childNode('obra', $serv);
        $evento = $this->childNode('atvEvento', $serv);
        $imovel = $this->childNode('imovel', $this->childNode('IBSCBS', $this->infDPS));
        $parts = [];
        $this->appendInfo($parts, 'Inf. Cont.: ', $this->value('xInfComp', $info));
        $this->appendInfo($parts, 'NFS-e Subst.: ', $this->value('chSubstda', $this->childNode('subst', $this->infDPS)));
        $this->appendInfo($parts, 'Doc. Ref.: ', $this->value('docRef', $info));
        $this->appendInfo($parts, 'Cod. Obra: ', $this->value('cObra', $obra));
        $this->appendInfo($parts, 'Insc. Imob.: ', $this->value('inscImobFisc', $obra) ?: $this->value('inscImobFisc', $imovel));
        $this->appendInfo($parts, 'Cod. Evt.: ', $this->value('idAtvEvt', $evento));
        $this->appendInfo($parts, 'Doc. Tec.: ', $this->value('idDocTec', $info));
        $this->appendInfo($parts, 'Núm. Ped.: ', $this->value('xPed', $info));
        $this->appendInfo($parts, 'Item Ped.: ', $this->value('xItemPed', $info));
        $this->appendInfo($parts, 'Inf. A. T. Mun.: ', $this->value('xOutInf', $info));

        $tax = $this->totaisAproximados();
        $prefix = implode(' | ', $parts);
        if ($prefix === '') {
            return $tax;
        }
        $limit = 2000 - mb_strlen(' | ' . $tax, 'UTF-8');
        if (mb_strlen($prefix, 'UTF-8') > $limit) {
            $prefix = mb_substr($prefix, 0, max(0, $limit - 3), 'UTF-8') . '...';
        }
        return $prefix . ' | ' . $tax;
    }

    private function appendInfo(array &$parts, $prefix, $value)
    {
        if ($value !== '') {
            $parts[] = $prefix . $value;
        }
    }

    private function totaisAproximados()
    {
        $dpsValores = $this->childNode('valores', $this->infDPS);
        $totTrib = $this->childNode('totTrib', $this->childNode('trib', $dpsValores));
        $fed = $this->firstValue(['vTotTribFed', 'pTotTribFed'], $totTrib);
        $est = $this->firstValue(['vTotTribEst', 'pTotTribEst'], $totTrib);
        $mun = $this->firstValue(['vTotTribMun', 'pTotTribMun'], $totTrib);
        return 'Totais Aproximados dos Tributos cfe. Lei nº 12.741/2012: Federais: '
            . $this->formatApproximateTax($fed) . ' ; Estaduais: ' . $this->formatApproximateTax($est)
            . ' ; Municipais: ' . $this->formatApproximateTax($mun);
    }

    private function printFederalTax()
    {
        $compet = $this->value('dCompet', $this->infDPS);
        if ($compet === '') {
            return true;
        }
        return substr($compet, 0, 4) <= '2026';
    }

    private function sumValues(array $values)
    {
        $sum = 0.0;
        $found = false;
        foreach ($values as $value) {
            $number = $this->normalizedNumber($value);
            if ($number !== null) {
                $sum += $number;
                $found = true;
            }
        }
        return $found ? number_format($sum, 2, '.', '') : '-';
    }

    private function formatDate($value)
    {
        if ($value === '') {
            return '';
        }
        try {
            return (new \DateTime($value))->format('d/m/Y');
        } catch (Exception $e) {
            return $value;
        }
    }

    private function formatDateTime($value)
    {
        if ($value === '') {
            return '';
        }
        try {
            return (new \DateTime($value))->format('d/m/Y H:i:s');
        } catch (Exception $e) {
            return $value;
        }
    }

    private function formatPhone($phone)
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 10) {
            return $this->formatField($digits, '(##) ####-####');
        }
        if (strlen($digits) === 11) {
            if (
                in_array(
                    substr($digits, 0, 4),
                    [
                        '0300',
                        '0303',
                        '0304',
                        '0500',
                        '0800',
                        '0900'
                    ]
                )
            ) {
                return $this->formatField($digits, '#### ### ####');
            } else {
                return $this->formatField($digits, '(##) #####-####');
            }
        }
        return $phone;
    }

    private function formatNbs($nbs)
    {
        $digits = preg_replace('/\D/', '', $nbs);
        if (strlen($digits) === 9) {
            return substr($digits, 0, 1) . '.' . substr($digits, 1, 4) . '.' . substr($digits, 5, 2)
                . '.' . substr($digits, 7, 2);
        }
        return $nbs;
    }

    private function percent($value)
    {
        if ($value === '') {
            return '';
        }
        $number = $this->normalizedNumber($value);
        if ($number === null) {
            return $value . '%';
        }
        return number_format($number, 2, ',', '.') . '%';
    }

    private function money($value)
    {
        if ($value === '' || $value === '-') {
            return $value;
        }
        $number = $this->normalizedNumber($value);
        if ($number === null) {
            return $value;
        }
        return 'R$ ' . number_format($number, 2, ',', '.');
    }

    private function formatApproximateTax($value)
    {
        if ($value === '') {
            return '-';
        }
        $number = $this->normalizedNumber($value);
        if ($number === null) {
            return $this->dash($value);
        }
        return 'R$ ' . number_format($number, 2, ',', '.');
    }

    private function normalizedNumber($value)
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return null;
        }
        $value = str_replace(' ', '', $value);
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            $value = str_replace('.', '', $value);
        }
        $value = str_replace(',', '.', $value);
        if (!is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }

    private function ellipsis($value, $max)
    {
        if (mb_strlen($value, 'UTF-8') <= $max) {
            return $value;
        }
        return mb_substr($value, 0, $max - 3, 'UTF-8') . '...';
    }

    private function dash($value)
    {
        $value = trim((string) $value);
        return $value === '' ? '-' : $value;
    }

    private function joinNonEmpty(array $values, $separator)
    {
        $out = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $out[] = $value;
            }
        }
        return implode($separator, $out);
    }

    private function tpAmb($value)
    {
        $map = ['1' => 'Produção', '2' => 'Homologação'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function tpEmit($value)
    {
        $map = ['1' => 'Prestador', '2' => 'Tomador', '3' => 'Intermediário'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function situacao($value)
    {
        $map = ['100' => 'NFS-e autorizada', '101' => 'NFS-e cancelada', '102' => 'NFS-e substituída'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function finalidade($value)
    {
        $map = ['1' => 'NFS-e regular', '2' => 'NFS-e de substituição', '3' => 'NFS-e de ajuste'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function opSimpNac($value)
    {
        $map = ['1' => 'Não Optante', '2' => 'Optante MEI', '3' => 'Optante Simples Nacional'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function regApTribSN($value)
    {
        $map = ['1' => 'Regime de apuração dos tributos federais e municipal pelo Simples Nacional'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function regEspTrib($value)
    {
        $map = ['0' => 'Nenhum', '1' => 'Microempresa Municipal', '2' => 'Estimativa'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function tribIssqn($value)
    {
        $map = ['1' => 'Operação Tributável', '2' => 'Imunidade', '3' => 'Exportação', '4' => 'Não Incidência'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function tpRetIssqn($value)
    {
        $map = ['1' => 'Não Retido', '2' => 'Retido pelo Tomador', '3' => 'Retido pelo Intermediário'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function tpRetPisCofins($value)
    {
        $map = [
            '0' => 'PIS/COFINS/CSLL Não Retidos',
            '1' => 'PIS/COFINS Retido',
            '2' => 'PIS/COFINS Não Retido',
            '3' => 'PIS/COFINS/CSLL Retidos',
            '4' => 'PIS/COFINS Retidos, CSLL Não Retido',
            '5' => 'PIS Retido, COFINS/CSLL Não Retido',
            '6' => 'COFINS Retido, PIS/CSLL Não Retido',
            '7' => 'PIS Não Retido, COFINS/CSLL Retidos',
            '8' => 'PIS/COFINS Não Retidos, CSLL Retido',
            '9' => 'COFINS Não Retido, PIS/CSLL Retidos'
        ];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function tpSusp($value)
    {
        $map = ['1' => 'Exigibilidade Suspensa por Decisão Judicial', '2' => 'Exigibilidade Suspensa por Processo Administrativo'];
        return isset($map[$value]) ? $map[$value] : $this->dash($value);
    }

    private function toIso($text)
    {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }
}
