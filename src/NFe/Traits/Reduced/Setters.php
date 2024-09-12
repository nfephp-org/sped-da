<?php

namespace NFePHP\DA\NFe\Traits\Reduced;

use Exception;

/**
 * @author Felipe Gabriel Hinkel <felipe.hinkel.dev@gmail.com>
 */
trait Setters
{
    public function setFont($font = 'arial')
    {
        $this->fontePadrao = !in_array($font, ['times', 'arial']) ? 'times' : $font;
        $this->aFont = ['font' => $this->fontePadrao, 'size' => 8, 'style' => ''];
    }

    public function setMargins($width = 1)
    {
        if ($width > 4 || $width < 0)
            throw new Exception("As margens devem estar entre 0 e 4 mm.");

        $this->margem = $width;
    }
}
