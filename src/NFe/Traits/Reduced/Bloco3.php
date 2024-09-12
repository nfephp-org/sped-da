<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Bloco3
{
    protected function bloco3($y)
    {
        $emitRazao = $this->getTagValue($this->emit, "xNome");
        $emitIE = $this->getTagValue($this->emit, "IE");
        $emitCnpj = $this->formatField($this->getTagValue($this->emit, "CNPJ"), "###.###.###/####-##");
        $emitFone = $this->formatPhone($this->getTagValue($this->enderEmit, "fone"));
        $emitEndereco = $this->formatEndereco();

        $h = 20;
        $maxHimg = $h - 2;
        [$xRs, $wRs, $y] = $this->processLogo($y, $h, $maxHimg);

        // Renderiza Razão Social
        $this->renderTextBox($emitRazao, $xRs + 2, $y, $wRs - 2, $this->margem - 1, ['font' => $this->fontePadrao, 'size' => 9, 'style' => 'B'], 'C');
        $y += 5;

        // Renderiza Endereço
        $this->renderTextBox($emitEndereco, $xRs + 2, $y, $wRs - 2, 3, ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''], 'C');
        $y += 5;

        // Renderiza CNPJ e IE
        $texto = "CNPJ: {$emitCnpj} IE: {$emitIE}";
        $this->renderTextBox($texto, $xRs + 2, $y, $wRs - 2, 3, ['font' => $this->fontePadrao, 'size' => 9, 'style' => ''], 'C');
        $y += 3;

        // Linha de separação
        $this->pdf->line($this->margem, $y + 3, $this->wPrint + $this->margem, $y + 3);

        return $y + 4;
    }

    private function formatEndereco()
    {
        $emitLgr = $this->getTagValue($this->enderEmit, "xLgr");
        $emitNro = $this->getTagValue($this->enderEmit, "nro");
        $emitBairro = $this->getTagValue($this->enderEmit, "xBairro");
        $emitMun = $this->getTagValue($this->enderEmit, "xMun");
        $emitUF = $this->getTagValue($this->enderEmit, "UF");

        return "{$emitBairro}, {$emitNro} - {$emitLgr}, {$emitMun} - {$emitUF}";
    }

    protected function formatPhone($phone)
    {
        if (empty($phone)) {
            return '';
        }

        return (strlen($phone) === 11)
            ? $this->formatField($phone, "(##) #####-####")
            : $this->formatField($phone, "(##) ####-####");
    }

    private function processLogo($y, $h, $maxHimg)
    {
        if (!empty($this->logomarca)) {
            $xImg = $this->margem + 2;
            [$nImgW, $nImgH] = $this->getImageDimensions($this->logomarca, $maxHimg);
            $xRs = ($nImgW) + $this->margem;
            $wRs = ($this->wPrint - $nImgW);
            $yImg = ($h - $nImgH) / 2 + $y;
            $this->pdf->image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
            return [$xRs, $wRs, $y];
        }

        // Caso não tenha logomarca
        return [$this->margem, $this->wPrint, $y];
    }

    private function getImageDimensions($imagePath, $maxHimg)
    {
        $logoInfo = getimagesize($imagePath);
        $logoWmm = ($logoInfo[0] / 72) * 25.4;
        $logoHmm = ($logoInfo[1] / 72) * 25.4;
        $nImgW = $this->wPrint / 4;
        $nImgH = round($logoHmm * ($nImgW / $logoWmm), 0);

        if ($nImgH > $maxHimg) {
            $nImgH = $maxHimg;
            $nImgW = round($logoWmm * ($nImgH / $logoHmm), 0);
        }

        return [$nImgW, $nImgH];
    }

    private function renderTextBox($text, $x, $y, $w, $h, $fontOptions, $align)
    {
        return $this->pdf->textBox(
            $x,
            $y,
            $w,
            $h,
            $text,
            $fontOptions,
            'T',
            $align,
            false,
            '',
            true
        );
    }
}
