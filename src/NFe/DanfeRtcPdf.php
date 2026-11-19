<?php

namespace NFePHP\DA\NFe;

use NFePHP\DA\Legacy\Pdf;

/**
 * Pdf da DanfeRtc: respiro entre o texto e a borda. Vale também para os quadros
 * herdados da Danfe, sem alterar o desenho nem o alinhamento dela.
 */
class DanfeRtcPdf extends Pdf
{
    /** Respiro horizontal e acima da linha de base, em mm. */
    const RESPIRO_X = 0.5;
    const RESPIRO_Y = 0.6;

    public function textBox(
        $x,
        $y,
        $w,
        $h,
        $text = '',
        $aFont = ['font' => 'Times', 'size' => 8, 'style' => ''],
        $vAlign = 'T',
        $hAlign = 'L',
        $border = true,
        $link = '',
        $force = true,
        $hmax = 0,
        $vOffSet = 0,
        $fill = false
    ) {
        if (is_int($text) || is_float($text)) {
            $text = (string) $text;
        }
        if (!is_string($text) || trim($text) === '') {
            return parent::textBox(
                $x,
                $y,
                $w,
                $h,
                $text,
                $aFont,
                $vAlign,
                $hAlign,
                $border,
                $link,
                $force,
                $hmax,
                $vOffSet,
                $fill
            );
        }
        // A borda fica onde estava, desenhada como no Pdf::textBox(); só o texto se afasta dela.
        if ($border && $fill) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'DF');
        } elseif ($border) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'D');
        } elseif ($fill) {
            $this->rect($x, $y, $w, $h, 'F');
        }
        // Valor de campo (negrito na base da caixa) sobe e desgruda da linha. A fatura
        // (7 pt) empilha rótulo e valor e fica como está.
        $valor = $vAlign === 'B' && ($aFont['style'] ?? '') === 'B' && $aFont['size'] >= 8;
        $hTexto = $valor ? $h - self::RESPIRO_Y : $h;
        $alt = parent::textBox(
            $x + self::RESPIRO_X,
            $y,
            $w - 2 * self::RESPIRO_X,
            $hTexto,
            $text,
            $aFont,
            $vAlign,
            $hAlign,
            0,
            $link,
            $force,
            $hmax,
            $vOffSet,
            false
        );

        return $alt;
    }
}
