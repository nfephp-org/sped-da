<?php

namespace NFePHP\DA\Common;

/**
 * Classe comum para a impressão em PDF do Documento Auxiliar de Bilhete de Passagem eletronico
 *
 * @category  Library
 * @package   nfephp-org/sped-da
 * @copyright 2009-2020 NFePHP
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3 or MIT
 * @link      http://github.com/nfephp-org/sped-da for the canonical source repository
 * @author    Roberto L. Machado <linux at rlm dot gmail dot com>
 */

use NFePHP\DA\Legacy\Common;

class DaCommon extends Common
{
    /**
     * @var bool
     */
    protected $debugmode;
    /**
     * @var string
     */
    protected $orientacao;
    /**
     * @var string|array
     */
    protected $papel = 'A4';
    /**
     * @var int
     */
    protected $margsup = 2;
    /**
     * @var int
     */
    protected $marginf = 2;
    /**
     * @var int
     */
    protected $margesq = 2;
    /**
     * @var float
     */
    protected $wPrint;
    /**
     * @var float
     */
    protected $hPrint;
    /**
     * @var float
     */
    protected $xIni;
    /**
     * @var float
     */
    protected $yIni;
    /**
     * @var float
     */
    protected $maxH;
    /**
     * @var float
     */
    protected $maxW;
    /**
     * @var string
     */
    protected $fontePadrao = 'times';
    /**
     * @var array
     */
    protected $aFont = ['font' => 'times', 'size' => 8, 'style' => ''];
    /**
     * @var string
     */
    protected $creditos;
    /**
     * @var bool
     */
    protected $powered = true;
    /**
     * @var string
     */
    protected $logomarca;
    /**
     * @var string
     */
    protected $logoAlign = 'C';
    /**
     * @var \NFePHP\DA\Legacy\Pdf
     */
    protected $pdf;
    /**
     * @var string
     */
    protected $numdepec;
    /**
     * @var int
     */
    protected $decimalPlaces;
    /**
     * @var bool
     */
    protected $cancelFlag = false;

    /**
     * Ativa ou desativa o modo debug
     * @param bool $activate Ativa ou desativa o modo debug
     * @return bool
     * @throws \Exception
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
            set_error_handler(function (int $errnum, string $errmsg, string $errfile, int $errline) {
                throw new \Exception("Erro identificado $errnum: '$errmsg' $errfile [linha:" . $errline . "]");
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
     * @return void
     */
    public function printParameters(
        $orientacao = '',
        $papel = 'A4',
        $margSup = 2,
        $margEsq = 2
    ) {
        if ($orientacao === 'P' || $orientacao === 'L') {
            $this->orientacao = $orientacao;
        }
        $p = strtoupper($papel);
        if ($p == 'A4' || $p == 'LEGAL') {
            $this->papel = $papel;
        }
        $this->margsup = $margSup ?? 2;
        $this->margesq = $margEsq ?? 2;
        $this->marginf = $margSup ?? 2;
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

    /**
     * Set logo e sua posição
     * @param string $logo
     * @param string $logoAlign
     * @param bool $mode_bw se true converte a imagem em branco e preto
     * @return void
     */
    public function logoParameters($logo, $logoAlign = null, $mode_bw = false)
    {
        if (!empty($logoAlign)) {
            $this->logoAlign = $logoAlign;
        }
        $this->logomarca = $this->adjustImage($logo, $mode_bw);
    }

    /**
     * Numero DPEC
     * @param string $numdepec
     * @return void
     */
    public function depecNumber($numdepec)
    {
        $this->numdepec = $numdepec;
    }

    /**
     * Renderiza o pdf e retorna como raw
     * @param string $logo
     * @return string
     */
    public function render(
        $logo = ''
    ) {
        if (empty($this->pdf)) {
            $this->monta($logo);
        }
        return $this->pdf->getPdf();
    }

    /**
     * Add the credits to the integrator in the footer message
     * @param string $message Mensagem do integrador a ser impressa no rodapé das paginas
     * @return void
     */
    public function creditsIntegratorFooter($message = '', $powered = true)
    {
        $this->creditos = trim($message);
        $this->powered = $powered;
    }

    /**
     * Seta a fonte padrão é times
     * @param string $font
     */
    public function setDefaultFont(string $font = 'times')
    {
        $this->fontePadrao = $font;
    }

    /**
     * Seta o numero de casas decimais a serem usadas como padrão
     * @param int $dec
     */
    public function setDefaultDecimalPlaces($dec)
    {
        if ($dec > 4) {
            $dec = 4;
        }
        if ($dec < 2) {
            $dec = 2;
        }
        $this->decimalPlaces = $dec;
    }

    /**
     * Metodo de montagem do PDF
     * @param string $logo
     */
    protected function monta($logo = null)
    {
        //todo replaced in other classes
    }

    /**
     * Ajusta a imagem do logo
     * @param string $logo
     * @param bool $turn_bw
     * @return string
     * @throws \Exception
     */
    protected function adjustImage($logo, $turn_bw = false)
    {
        if (!empty($this->logomarca)) {
            return $this->logomarca;
        }
        if (empty($logo)) {
            return null;
        }
        if (substr($logo, 0, 24) !== 'data://text/plain;base64') {
            if (is_file($logo)) {
                $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents($logo));
            } else {
                //se não é uma string e nem um file retorna nulo
                return null;
            }
        }
        $logoInfo = getimagesize($logo);
        //1 = GIF, 2 = JPG, 3 = PNG, 4 = SWF, 5 = PSD, 6 = BMP, 7 = TIFF(intel byte order),
        //8 = TIFF(motorola byte order), 9 = JPC, 10 = JP2, 11 = JPX, 12 = JB2, 13 = SWC,
        //14 = IFF, 15 = WBMP, 16 = XBM
        $type = $logoInfo[2];
        if ($type != '2' && $type != '3') {
            throw new \Exception('O formato da imagem não é aceitável! Somente PNG ou JPG podem ser usados.');
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

    /**
     * Cria uma imagem JPEG com o objeto GD
     * @param resource $image
     * @return string
     */
    private function getImageStringFromObject($image)
    {
        ob_start();
        imagejpeg($image, null, 100);
        imagedestroy($image);
        $logo = ob_get_contents(); // read from buffer
        ob_end_clean();
        return 'data://text/plain;base64,'.base64_encode($logo);
    }

    /**
     * Atribui uma sinalização de cancelamento externa
     * @param bool $cancelFlag
     */
    public function setCancelFlag($cancelFlag = true)
    {
        $this->cancelFlag = filter_var($cancelFlag, FILTER_VALIDATE_BOOLEAN);
    }
}
