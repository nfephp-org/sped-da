<?php

namespace NFePHP\DA\Common;

use NFePHP\DA\Legacy\Common;

class DaCommon extends Common
{

    protected $debugmode;
    protected $orientacao = 'P';
    protected $force;
    protected $papel = 'A4';
    protected $margsup = 2;
    protected $margesq = 2;
    protected $wPrint;
    protected $hPrint;
    protected $xIni;
    protected $yIni;
    protected $maxH;
    protected $maxW;
    protected $fontePadrao = 'times';
    protected $aFont = ['font' => 'times', 'size' => 8, 'style' => ''];
    protected $creditos;
    protected $logomarca = '';
    protected $logotype = 'jpg';

    /**
     * Ativa ou desativa o modo debug
     *
     * @param bool $activate Ativa ou desativa o modo debug
     *
     * @return bool
     */
    public function debugMode($activate = null)
    {
        if (isset($activate) && is_bool($activate)) {
            $this->debugmode = $activate;
        }
        if ($this->debugmode) {
            //ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
            set_error_handler(function (int $number, string $message, string $errfile, int $errline) {
                throw new \Exception("Handler captured error $number: '$message' $errfile [linha:" . $errline . "]");
            });
        } else {
            //desativar modo debug
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        return $this->debugmode;
    }
    
    /**
     * Define parametros de impressão
     * @param string $orientacao
     * @param string $papel
     * @param int $margSup
     * @param int $margEsq
     */
    public function printParameters(
        $orientacao = 'P',
        $papel = 'A4',
        $margSup = null,
        $margEsq = null,
        $logoAlign = null
    ) {
        if ($orientação === 'P' || $orientacao === 'L') {
            $this->force = $orientacao;
        }
        $p = strtoupper($papel);
        if ($p == 'A4' || $p == 'LEGAL') {
            $this->papel = $papel;
        }
        $this->margsup = $margSup ?? 2;
        $this->margesq = $margEsq ?? 2;
        if (!empty($logoAlign)) {
            $this->logoAlign = $logoAlign;
        }
    }

    /**
     * Renderiza o pdf e retorna como raw
     *
     * @return string
     */
    public function render(
        $logo = '',
        $depecNumReg = ''
    ) {
        if (empty($this->pdf)) {
            $this->monta($logo, $depecNumReg);
        }
        return $this->pdf->getPdf();
    }

    /**
     * Add the credits to the integrator in the footer message
     *
     * @param string $message Mensagem do integrador a ser impressa no rodapé das paginas
     *
     * @return void
     */
    public function creditsIntegratorFooter($message = '')
    {
        $this->creditos = trim($message);
    }

    /**
     *
     * @param string $font
     */
    public function setFontType(string $font = 'times')
    {
        $this->aFont['font'] = $font;
    }

    /**
     * Seta o tamanho da fonte
     *
     * @param int $size
     *
     * @return void
     */
    protected function setFontSize(int $size = 8)
    {
        $this->aFont['size'] = $size;
    }

    /**
     *
     * @param string $style
     */
    protected function setFontStyle(string $style = '')
    {
        $this->aFont['style'] = $style;
    }

    /**
     * Seta a orientação
     *
     * @param string $force
     * @param string $tpImp
     *
     * @return void
     */
    protected function setOrientationAndSize($force = null, $tpImp = null)
    {
        $this->orientacao = 'P';
        if (!empty($force)) {
            $this->orientacao = $force;
        } elseif (!empty($tpImp)) {
            if ($tpImp == '2') {
                $this->orientacao = 'L';
            } else {
                $this->orientacao = 'P';
            }
        }
        if (strtoupper($this->papel) == 'A4') {
            $this->maxW = 210;
            $this->maxH = 297;
        } else {
            $this->papel = 'legal';
            $this->maxW = 216;
            $this->maxH = 355;
        }
        if ($this->orientacao == 'L') {
            if (strtoupper($this->papel) == 'A4') {
                $this->maxH = 210;
                $this->maxW = 297;
            } else {
                $this->papel = 'legal';
                $this->maxH = 216;
                $this->maxW = 355;
            }
        }
        $this->wPrint = $this->maxW - $this->margesq * 2;
        $this->hPrint = $this->maxH - $this->margsup - 5;
    }
    
    protected function adjustImage($logo, $turn_bw = false)
    {
        if (substr($logo, 0, 24) !== 'data://text/plain;base64') {
            if (is_file($logo)) {
                $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents($logo));
            } else {
                $logo = '';
            }
        }
        $logoInfo = getimagesize($logo);
        //1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order),
        //8 = TIFF(motorola byte order), 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC,
        //14 = IFF, 15 = WBMP, 16 = XBM
        $type = $logoInfo[2];
        if ($type != '2' && $type != '3') {
            throw new Exception('O formato da imagem não é aceitável! Somente PNG ou JPG podem ser usados.');
        }
        if ($type == '3') { //3 = PNG
            $image = imagecreatefrompng($logo);
            if ($turn_bw) {
                imagefilter($image, IMG_FILTER_GRAYSCALE);
                //imagefilter($image, IMG_FILTER_CONTRAST, -100);
            }
            return $this->getImageStringFromObject($image);
        } elseif ($type == '2' && $turn_bw) {
            $image = imagecreatefromjpeg($logo);
            imagefilter($image, IMG_FILTER_GRAYSCALE);
            //imagefilter($image, IMG_FILTER_CONTRAST, -100);
            return $this->getImageStringFromObject($image);
        }
        return $logo;
    }
    
    private function getImageStringFromObject($image)
    {
        ob_start();
        imagejpeg($image, null, 100);
        imagedestroy($image);
        $logo = ob_get_contents(); // read from buffer
        ob_end_clean();
        return 'data://text/plain;base64,'.base64_encode($logo);
    }
}
