<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitWaterMark
{
    public function waterMark()
    {
        $wm = "waterMark{$this->orientacao}";
        $this->$wm();
    }

    protected function waterMarkP()
    {
        $message = '';
        $submessage = '';
        if (empty($this->protNFe)) {
            //não está protocolado
            $message = 'IMPRESSÃO EM TESTE';
            $submessage = "SEM PROTOCOLO DE AUTORIZAÇÃO\nSEM VALOR FISCAL";
        } else {
            if ($this->protNFe->infProt->cStat === '101') {
                $message = 'NFe CANCELADA';
                $submessage = '';
            }
        }
        if (!empty($this->retEvento)) {
            //pode estar cancelada
            if ($this->retEvento->infEvento->tpEvento === '110111') {
                $message = 'NFe CANCELADA';
                $dh = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $this->retEvento->infEvento->dhRegEvento);
                $protocolo = $this->retEvento->infEvento->nProt;
                $submessage = "Data canc.: " . $dh->format('d/m/Y H:i:s') . "\nProtocolo: " . $protocolo;
            }
        }
        if ($this->std->ide->tpAmb === 2) {
            //homologação
            $message = "DOCUMENTO EMITIDO\nEM HOMOLOGAÇÃO";
            $submessage = "SEM VALOR FISCAL";
        }
        $this->showMessage($message, $submessage);
    }

    protected function waterMarkL()
    {
        $message = '';
        $submessage = '';
        $this->showMessage($message, $submessage);
    }

    /**
     * Exibe a marca d'água
     * @param string $message
     * @param string $submessage
     * @return void
     */
    protected function showMessage(string $message, string $submessage)
    {
        $y = $this->hPrint/2;
        $h = 25;
        $h1 = 15;
        $this->pdf->settextcolor(200, 200, 200);
        $aFont = ['font' => $this->fontePadrao, 'size' => 60, 'style' => 'B'];
        $this->pdf->textBox($this->margesq, $y, $this->wPrint, $h, $message, $aFont, 'T', 'C', 0, '');
        $lines = $this->pdf->getNumLines($message, $this->wPrint, $aFont);
        if ($lines > 1) {
            $y = $y + $lines * $h1;
        } else {
            $y = $y + $h;
        }
        $h = 5;
        $aFont = ['font' => $this->fontePadrao, 'size' => 40, 'style' => 'B'];
        $this->pdf->textBox($this->margesq, $y, $this->wPrint, $h, $submessage, $aFont, 'C', 'C', 0, '');
        $this->pdf->settextcolor(0, 0, 0);
    }
}
