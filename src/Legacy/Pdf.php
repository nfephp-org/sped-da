<?php

namespace NFePHP\DA\Legacy;

use NFePHP\DA\Legacy\FPDF\Fpdf as Fpdf;

class Pdf extends Fpdf
{
    private $t128;                                             // tabela de codigos 128
    private $abcSet="";                                        // conjunto de caracteres legiveis em 128
    private $aSet="";                                          // grupo A do conjunto de de caracteres legiveis
    private $bSet="";                                          // grupo B do conjunto de caracteres legiveis
    private $cSet="";                                          // grupo C do conjunto de caracteres legiveis
    private $setFrom;                                          // converter de
    private $setTo;                                            // converter para
    private $jStart = ["A"=>103, "B"=>104, "C"=>105];     // Caracteres de seleção do grupo 128
    private $jSwap = ["A"=>101, "B"=>100, "C"=>99];       // Caracteres de troca de grupo

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        //passar parametros para a classe principal
        parent::__construct($orientation, $unit, $format);
        // composição dos caracteres do barcode 128
        $this->t128[] = array(2, 1, 2, 2, 2, 2);           //0 : [ ]
        $this->t128[] = array(2, 2, 2, 1, 2, 2);           //1 : [!]
        $this->t128[] = array(2, 2, 2, 2, 2, 1);           //2 : ["]
        $this->t128[] = array(1, 2, 1, 2, 2, 3);           //3 : [#]
        $this->t128[] = array(1, 2, 1, 3, 2, 2);           //4 : [$]
        $this->t128[] = array(1, 3, 1, 2, 2, 2);           //5 : [%]
        $this->t128[] = array(1, 2, 2, 2, 1, 3);           //6 : [&]
        $this->t128[] = array(1, 2, 2, 3, 1, 2);           //7 : [']
        $this->t128[] = array(1, 3, 2, 2, 1, 2);           //8 : [(]
        $this->t128[] = array(2, 2, 1, 2, 1, 3);           //9 : [)]
        $this->t128[] = array(2, 2, 1, 3, 1, 2);           //10 : [*]
        $this->t128[] = array(2, 3, 1, 2, 1, 2);           //11 : [+]
        $this->t128[] = array(1, 1, 2, 2, 3, 2);           //12 : [,]
        $this->t128[] = array(1, 2, 2, 1, 3, 2);           //13 : [-]
        $this->t128[] = array(1, 2, 2, 2, 3, 1);           //14 : [.]
        $this->t128[] = array(1, 1, 3, 2, 2, 2);           //15 : [/]
        $this->t128[] = array(1, 2, 3, 1, 2, 2);           //16 : [0]
        $this->t128[] = array(1, 2, 3, 2, 2, 1);           //17 : [1]
        $this->t128[] = array(2, 2, 3, 2, 1, 1);           //18 : [2]
        $this->t128[] = array(2, 2, 1, 1, 3, 2);           //19 : [3]
        $this->t128[] = array(2, 2, 1, 2, 3, 1);           //20 : [4]
        $this->t128[] = array(2, 1, 3, 2, 1, 2);           //21 : [5]
        $this->t128[] = array(2, 2, 3, 1, 1, 2);           //22 : [6]
        $this->t128[] = array(3, 1, 2, 1, 3, 1);           //23 : [7]
        $this->t128[] = array(3, 1, 1, 2, 2, 2);           //24 : [8]
        $this->t128[] = array(3, 2, 1, 1, 2, 2);           //25 : [9]
        $this->t128[] = array(3, 2, 1, 2, 2, 1);           //26 : [:]
        $this->t128[] = array(3, 1, 2, 2, 1, 2);           //27 : [;]
        $this->t128[] = array(3, 2, 2, 1, 1, 2);           //28 : [<]
        $this->t128[] = array(3, 2, 2, 2, 1, 1);           //29 : [=]
        $this->t128[] = array(2, 1, 2, 1, 2, 3);           //30 : [>]
        $this->t128[] = array(2, 1, 2, 3, 2, 1);           //31 : [?]
        $this->t128[] = array(2, 3, 2, 1, 2, 1);           //32 : [@]
        $this->t128[] = array(1, 1, 1, 3, 2, 3);           //33 : [A]
        $this->t128[] = array(1, 3, 1, 1, 2, 3);           //34 : [B]
        $this->t128[] = array(1, 3, 1, 3, 2, 1);           //35 : [C]
        $this->t128[] = array(1, 1, 2, 3, 1, 3);           //36 : [D]
        $this->t128[] = array(1, 3, 2, 1, 1, 3);           //37 : [E]
        $this->t128[] = array(1, 3, 2, 3, 1, 1);           //38 : [F]
        $this->t128[] = array(2, 1, 1, 3, 1, 3);           //39 : [G]
        $this->t128[] = array(2, 3, 1, 1, 1, 3);           //40 : [H]
        $this->t128[] = array(2, 3, 1, 3, 1, 1);           //41 : [I]
        $this->t128[] = array(1, 1, 2, 1, 3, 3);           //42 : [J]
        $this->t128[] = array(1, 1, 2, 3, 3, 1);           //43 : [K]
        $this->t128[] = array(1, 3, 2, 1, 3, 1);           //44 : [L]
        $this->t128[] = array(1, 1, 3, 1, 2, 3);           //45 : [M]
        $this->t128[] = array(1, 1, 3, 3, 2, 1);           //46 : [N]
        $this->t128[] = array(1, 3, 3, 1, 2, 1);           //47 : [O]
        $this->t128[] = array(3, 1, 3, 1, 2, 1);           //48 : [P]
        $this->t128[] = array(2, 1, 1, 3, 3, 1);           //49 : [Q]
        $this->t128[] = array(2, 3, 1, 1, 3, 1);           //50 : [R]
        $this->t128[] = array(2, 1, 3, 1, 1, 3);           //51 : [S]
        $this->t128[] = array(2, 1, 3, 3, 1, 1);           //52 : [T]
        $this->t128[] = array(2, 1, 3, 1, 3, 1);           //53 : [U]
        $this->t128[] = array(3, 1, 1, 1, 2, 3);           //54 : [V]
        $this->t128[] = array(3, 1, 1, 3, 2, 1);           //55 : [W]
        $this->t128[] = array(3, 3, 1, 1, 2, 1);           //56 : [X]
        $this->t128[] = array(3, 1, 2, 1, 1, 3);           //57 : [Y]
        $this->t128[] = array(3, 1, 2, 3, 1, 1);           //58 : [Z]
        $this->t128[] = array(3, 3, 2, 1, 1, 1);           //59 : [[]
        $this->t128[] = array(3, 1, 4, 1, 1, 1);           //60 : [\]
        $this->t128[] = array(2, 2, 1, 4, 1, 1);           //61 : []]
        $this->t128[] = array(4, 3, 1, 1, 1, 1);           //62 : [^]
        $this->t128[] = array(1, 1, 1, 2, 2, 4);           //63 : [_]
        $this->t128[] = array(1, 1, 1, 4, 2, 2);           //64 : [`]
        $this->t128[] = array(1, 2, 1, 1, 2, 4);           //65 : [a]
        $this->t128[] = array(1, 2, 1, 4, 2, 1);           //66 : [b]
        $this->t128[] = array(1, 4, 1, 1, 2, 2);           //67 : [c]
        $this->t128[] = array(1, 4, 1, 2, 2, 1);           //68 : [d]
        $this->t128[] = array(1, 1, 2, 2, 1, 4);           //69 : [e]
        $this->t128[] = array(1, 1, 2, 4, 1, 2);           //70 : [f]
        $this->t128[] = array(1, 2, 2, 1, 1, 4);           //71 : [g]
        $this->t128[] = array(1, 2, 2, 4, 1, 1);           //72 : [h]
        $this->t128[] = array(1, 4, 2, 1, 1, 2);           //73 : [i]
        $this->t128[] = array(1, 4, 2, 2, 1, 1);           //74 : [j]
        $this->t128[] = array(2, 4, 1, 2, 1, 1);           //75 : [k]
        $this->t128[] = array(2, 2, 1, 1, 1, 4);           //76 : [l]
        $this->t128[] = array(4, 1, 3, 1, 1, 1);           //77 : [m]
        $this->t128[] = array(2, 4, 1, 1, 1, 2);           //78 : [n]
        $this->t128[] = array(1, 3, 4, 1, 1, 1);           //79 : [o]
        $this->t128[] = array(1, 1, 1, 2, 4, 2);           //80 : [p]
        $this->t128[] = array(1, 2, 1, 1, 4, 2);           //81 : [q]
        $this->t128[] = array(1, 2, 1, 2, 4, 1);           //82 : [r]
        $this->t128[] = array(1, 1, 4, 2, 1, 2);           //83 : [s]
        $this->t128[] = array(1, 2, 4, 1, 1, 2);           //84 : [t]
        $this->t128[] = array(1, 2, 4, 2, 1, 1);           //85 : [u]
        $this->t128[] = array(4, 1, 1, 2, 1, 2);           //86 : [v]
        $this->t128[] = array(4, 2, 1, 1, 1, 2);           //87 : [w]
        $this->t128[] = array(4, 2, 1, 2, 1, 1);           //88 : [x]
        $this->t128[] = array(2, 1, 2, 1, 4, 1);           //89 : [y]
        $this->t128[] = array(2, 1, 4, 1, 2, 1);           //90 : [z]
        $this->t128[] = array(4, 1, 2, 1, 2, 1);           //91 : [{]
        $this->t128[] = array(1, 1, 1, 1, 4, 3);           //92 : [|]
        $this->t128[] = array(1, 1, 1, 3, 4, 1);           //93 : [}]
        $this->t128[] = array(1, 3, 1, 1, 4, 1);           //94 : [~]
        $this->t128[] = array(1, 1, 4, 1, 1, 3);           //95 : [DEL]
        $this->t128[] = array(1, 1, 4, 3, 1, 1);           //96 : [FNC3]
        $this->t128[] = array(4, 1, 1, 1, 1, 3);           //97 : [FNC2]
        $this->t128[] = array(4, 1, 1, 3, 1, 1);           //98 : [SHIFT]
        $this->t128[] = array(1, 1, 3, 1, 4, 1);           //99 : [Cswap]
        $this->t128[] = array(1, 1, 4, 1, 3, 1);           //100 : [Bswap]
        $this->t128[] = array(3, 1, 1, 1, 4, 1);           //101 : [Aswap]
        $this->t128[] = array(4, 1, 1, 1, 3, 1);           //102 : [FNC1]
        $this->t128[] = array(2, 1, 1, 4, 1, 2);           //103 : [Astart]
        $this->t128[] = array(2, 1, 1, 2, 1, 4);           //104 : [Bstart]
        $this->t128[] = array(2, 1, 1, 2, 3, 2);           //105 : [Cstart]
        $this->t128[] = array(2, 3, 3, 1, 1, 1);           //106 : [STOP]
        $this->t128[] = array(2, 1);                       //107 : [END BAR]
        for ($i = 32; $i <= 95; $i++) {   // conjunto de caracteres
            $this->abcSet .= chr($i);
        }
        $this->aSet = $this->abcSet;
        $this->bSet = $this->abcSet;
        for ($i = 0; $i <= 31; $i++) {
            $this->abcSet .= chr($i);
            $this->aSet .= chr($i);
        }
        for ($i = 96; $i <= 126; $i++) {
            $this->abcSet .= chr($i);
            $this->bSet .= chr($i);
        }
        $this->cSet="0123456789";
        for ($i = 0; $i < 96; $i++) {
            // convertendo grupos A & B
            if (isset($this->setFrom["A"])) {
                $this->setFrom["A"] .= chr($i);
            }
            if (isset($this->setFrom["B"])) {
                $this->setFrom["B"] .= chr($i + 32);
            }
            if (isset($this->setTo["A"])) {
                $this->setTo["A"] .= chr(($i < 32) ? $i+64 : $i-32);
            }
            if (isset($this->setTo["A"])) {
                $this->setTo["B"] .= chr($i);
            }
        }
    }

    /**
     * Imprime barcode 128
     */
    public function code128($x, $y, $code, $w, $h)
    {
        $Aguid="";
        $Bguid="";
        $Cguid="";
        for ($i=0; $i < strlen($code); $i++) {
            $needle=substr($code, $i, 1);
            $Aguid .= ((strpos($this->aSet, $needle)===false) ? "N" : "O");
            $Bguid .= ((strpos($this->bSet, $needle)===false) ? "N" : "O");
            $Cguid .= ((strpos($this->cSet, $needle)===false) ? "N" : "O");
        }
        $SminiC = "OOOO";
        $IminiC = 4;
        $crypt = "";
        while ($code > "") {
            $i = strpos($Cguid, $SminiC);
            if ($i!==false) {
                $Aguid [$i] = "N";
                $Bguid [$i] = "N";
            }
            if (substr($Cguid, 0, $IminiC) == $SminiC) {
                $crypt .= chr(($crypt > "") ? $this->jSwap["C"] : $this->jStart["C"]);
                $made = strpos($Cguid, "N");
                if ($made === false) {
                    $made = strlen($Cguid);
                }
                if (fmod($made, 2)==1) {
                    $made--;
                }
                for ($i=0; $i < $made; $i += 2) {
                    $crypt .= chr(strval(substr($code, $i, 2)));
                }
                    $jeu = "C";
            } else {
                $madeA = strpos($Aguid, "N");
                if ($madeA === false) {
                    $madeA = strlen($Aguid);
                }
                $madeB = strpos($Bguid, "N");
                if ($madeB === false) {
                    $madeB = strlen($Bguid);
                }
                $made = (($madeA < $madeB) ? $madeB : $madeA );
                $jeu = (($madeA < $madeB) ? "B" : "A" );
                $jeuguid = $jeu . "guid";
                $crypt .= chr(($crypt > "") ? $this->jSwap["$jeu"] : $this->jStart["$jeu"]);
                $crypt .= strtr(substr($code, 0, $made), $this->setFrom[$jeu], $this->setTo[$jeu]);
            }
            $code = substr($code, $made);
            $Aguid = substr($Aguid, $made);
            $Bguid = substr($Bguid, $made);
            $Cguid = substr($Cguid, $made);
        }
        $check = ord($crypt[0]);
        for ($i=0; $i<strlen($crypt); $i++) {
            $check += (ord($crypt[$i]) * $i);
        }
        $check %= 103;
        $crypt .= chr($check) . chr(106) . chr(107);
        $i = (strlen($crypt) * 11) - 8;
        $modul = $w/$i;
        for ($i=0; $i<strlen($crypt); $i++) {
            $c = $this->t128[ord($crypt[$i])];
            for ($j=0; $j<count($c); $j++) {
                $this->Rect($x, $y, $c[$j]*$modul, $h, "F");
                $x += ($c[$j++]+$c[$j])*$modul;
            }
        }
    }

    /**
     * Rotaciona para impressão paisagem (landscape)
     * @param   number $angle
     * @param   number $x
     * @param   number $y
     */
    public function rotate($angle, $x = -1, $y = -1)
    {
        if ($x == -1) {
            $x = $this->x;
        }
        if ($y == -1) {
            $y = $this->y;
        }
        if (isset($this->angle) && $this->angle != 0) {
            $this->out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI/180;
            $c = cos($angle);
            $s = sin($angle);
            $cx =$x*$this->k;
            $cy = ($this->h-$y)*$this->k;
            $this->out(
                sprintf(
                    'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                    $c,
                    $s,
                    -$s,
                    $c,
                    $cx,
                    $cy,
                    -$cx,
                    -$cy
                )
            );
        }
    }

    /**
     * Desenha um retangulo com cantos arredondados
     * @param   number $x
     * @param   number $y
     * @param   number $w
     * @param   number $h
     * @param   number $r
     * @param   string $corners
     * @param   string $style
     */
    public function roundedRect($x, $y, $w, $h, $r, $corners = '1234', $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F') {
            $op = 'f';
        } elseif ($style == 'FD' || $style == 'DF') {
            $op = 'B';
        } else {
            $op = 'S';
        }
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->out(sprintf('%.2F %.2F m', ($x+$r)*$k, ($hp-$y)*$k));
        $xc = $x+$w-$r;
        $yc = $y+$r;
        $this->out(sprintf('%.2F %.2F l', $xc*$k, ($hp-$y)*$k));
        if (strpos($corners, '2')===false) {
            $this->out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$y)*$k));
        } else {
            $this->arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        }
        $xc = $x+$w-$r;
        $yc = $y+$h-$r;
        $this->out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-$yc)*$k));
        if (strpos($corners, '3')===false) {
            $this->out(sprintf('%.2F %.2F l', ($x+$w)*$k, ($hp-($y+$h))*$k));
        } else {
            $this->arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        }
        $xc = $x+$r;
        $yc = $y+$h-$r;
        $this->out(sprintf('%.2F %.2F l', $xc*$k, ($hp-($y+$h))*$k));
        if (strpos($corners, '4')===false) {
            $this->out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-($y+$h))*$k));
        } else {
            $this->arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        }
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$yc)*$k));
        if (strpos($corners, '1')===false) {
            $this->out(sprintf('%.2F %.2F l', ($x)*$k, ($hp-$y)*$k));
            $this->out(sprintf('%.2F %.2F l', ($x+$r)*$k, ($hp-$y)*$k));
        } else {
            $this->arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        }
        $this->out($op);
    }
    
    /**
     * Desenha o arco para arredondar o canto do retangulo
     * @param   number $x1
     * @param   number $y1
     * @param   number $x2
     * @param   number $y2
     * @param   number $x3
     * @param   number $y3
     */
    private function arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->out(
            sprintf(
                '%.2F %.2F %.2F %.2F %.2F %.2F c ',
                $x1*$this->k,
                ($h-$y1)*$this->k,
                $x2*$this->k,
                ($h-$y2)*$this->k,
                $x3*$this->k,
                ($h-$y3)*$this->k
            )
        );
    }
    
    /**
     * Desenha um retangulo com linhas tracejadas
     * @param   number $x1
     * @param   number $y1
     * @param   number $x2
     * @param   number $y2
     * @param   number $width
     * @param   number $nb
     */
    public function dashedRect($x1, $y1, $x2, $y2, $width = 1, $nb = 15)
    {
        $this->setLineWidth($width);
        $longueur = abs($x1-$x2);
        $hauteur = abs($y1-$y2);
        if ($longueur > $hauteur) {
            $Pointilles = ($longueur/$nb)/2;
        } else {
            $Pointilles = ($hauteur/$nb)/2;
        }
        for ($i=$x1; $i<=$x2; $i+=$Pointilles+$Pointilles) {
            for ($j=$i; $j<=($i+$Pointilles); $j++) {
                if ($j<=($x2-1)) {
                    $this->line($j, $y1, $j+1, $y1);
                    $this->line($j, $y2, $j+1, $y2);
                }
            }
        }
        for ($i=$y1; $i<=$y2; $i+=$Pointilles+$Pointilles) {
            for ($j=$i; $j<=($i+$Pointilles); $j++) {
                if ($j<=($y2-1)) {
                    $this->line($x1, $j, $x1, $j+1);
                    $this->line($x2, $j, $x2, $j+1);
                }
            }
        }
    }

    /**
     * Monta uma caixa de texto
     * @param   string  $strText
     * @param   number  $w
     * @param   number  $h
     * @param   string  $align
     * @param   string  $valign
     * @param   boolean $border
     */
    public function drawTextBox($strText, $w, $h, $align = 'L', $valign = 'T', $border = true)
    {
        $xi = $this->getX();
        $yi = $this->getY();
        $hrow = $this->fontSize;
        $textrows = $this->drawRows($w, $hrow, $strText, 0, $align, 0, 0, 0);
        $maxrows = floor($h/$this->fontSize);
        $rows = min($textrows, $maxrows);
        $dy = 0;
        if (strtoupper($valign) == 'M') {
            $dy = ($h-$rows*$this->fontSize)/2;
        }
        if (strtoupper($valign) == 'B') {
            $dy = $h-$rows*$this->fontSize;
        }
        $this->setY($yi+$dy);
        $this->setX($xi);
        $this->drawRows($w, $hrow, $strText, 0, $align, false, $rows, 1);
        if ($border) {
            $this->rect($xi, $yi, $w, $h);
        }
    }
    
    /**
     * Insere linhas de texto na caixa
     * @param   number  $w
     * @param   number  $h
     * @param   string  $txt
     * @param   string  $border
     * @param   string  $align
     * @param   boolean $fill
     * @param   number  $maxline
     * @param   number  $prn
     * @return  int
     */
    private function drawRows($w, $h, $txt, $border = 0, $align = 'J', $fill = false, $maxline = 0, $prn = 0)
    {
        $cw =& $this->currentFont['cw'];
        if ($w == 0) {
            $w = $this->w-$this->rMargin-$this->x;
        }
        $wmax = ($w-2*$this->cMargin)*1000/$this->fontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb-1] == "\n") {
            $nb--;
        }
        $b=0;
        if ($border) {
            if ($border == 1) {
                $border = 'LTRB';
                $b = 'LRT';
                $b2 = 'LR';
            } else {
                $b2 = '';
                if (is_int(strpos($border, 'L'))) {
                    $b2 .= 'L';
                }
                if (is_int(strpos($border, 'R'))) {
                    $b2 .= 'R';
                }
                $b = is_int(strpos($border, 'T')) ? $b2.'T' : $b2;
            }
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $ns = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                if ($this->ws > 0) {
                    $this->ws = 0;
                    if ($prn == 1) {
                        $this->out('0 Tw');
                    }
                }
                if ($prn == 1) {
                    $this->cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                }
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
                if ($maxline && $nl > $maxline) {
                    return substr($s, $i);
                }
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
                $ls = $l;
                $ns++;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                    if ($this->ws > 0) {
                        $this->ws = 0;
                        if ($prn == 1) {
                            $this->out('0 Tw');
                        }
                    }
                    if ($prn == 1) {
                        $this->cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
                    }
                } else {
                    if ($align == 'J') {
                        $this->ws = ($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
                        if ($prn == 1) {
                            $this->out(sprintf('%.3F Tw', $this->ws*$this->k));
                        }
                    }
                    if ($prn == 1) {
                        $this->cell($w, $h, substr($s, $j, $sep-$j), $b, 2, $align, $fill);
                    }
                    $i = $sep+1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $ns = 0;
                $nl++;
                if ($border && $nl == 2) {
                    $b = $b2;
                }
                if ($maxline && $nl > $maxline) {
                    return substr($s, $i);
                }
            } else {
                $i++;
            }
        }
        if ($this->ws > 0) {
            $this->ws = 0;
            if ($prn == 1) {
                $this->out('0 Tw');
            }
        }
        if ($border && is_int(strpos($border, 'B'))) {
            $b .= 'B';
        }
        if ($prn == 1) {
            $this->cell($w, $h, substr($s, $j, $i-$j), $b, 2, $align, $fill);
        }
        $this->x = $this->lMargin;
        return $nl;
    }
    
    /**
     * Quebra o texto para caber na caixa
     * @param   type $text
     * @param   type $maxwidth
     * @return  int
     */
    public function wordWrap(&$text, $maxwidth)
    {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }
        $space = $this->getStringWidth(' ');
        $lines = explode("\n", $text);
        $text = '';
        $count = 0;
        foreach ($lines as $line) {
            $words = preg_split('/ +/', $line);
            $width = 0;
            foreach ($words as $word) {
                $wordwidth = $this->getStringWidth($word);
                if ($wordwidth > $maxwidth) {
                    // Word is too long, we cut it
                    for ($i=0; $i<strlen($word); $i++) {
                        $wordwidth = $this->getStringWidth(substr($word, $i, 1));
                        if ($width + $wordwidth <= $maxwidth) {
                            $width += $wordwidth;
                            $text .= substr($word, $i, 1);
                        } else {
                            $width = $wordwidth;
                            $text = rtrim($text)."\n".substr($word, $i, 1);
                            $count++;
                        }
                    }
                } elseif ($width + $wordwidth <= $maxwidth) {
                    $width += $wordwidth + $space;
                    $text .= $word.' ';
                } else {
                    $width = $wordwidth + $space;
                    $text = rtrim($text)."\n".$word.' ';
                    $count++;
                }
            }
            $text = rtrim($text)."\n";
            $count++;
        }
        $text = rtrim($text);
        return $count;
    }
    
    /**
     * Celula com escala horizontal caso o texto seja muito largo
     * @param   number  $w
     * @param   number  $h
     * @param   string  $txt
     * @param   number  $border
     * @param   number  $ln
     * @param   string  $align
     * @param   boolean $fill
     * @param   string  $link
     * @param   boolean $scale
     * @param   boolean $force
     */
    public function cellFit(
        $w,
        $h = 0,
        $txt = '',
        $border = 0,
        $ln = 0,
        $align = '',
        $fill = false,
        $link = '',
        $scale = false,
        $force = true
    ) {
        $str_width=$this->getStringWidth($txt);
        if ($w == 0) {
            $w = $this->w-$this->rMargin-$this->x;
        }
        $ratio = ($w-$this->cMargin*2)/$str_width;
        $fit = ($ratio < 1 || ($ratio > 1 && $force));
        if ($fit) {
            if ($scale) {
                //Calcula a escala horizontal
                $horiz_scale = $ratio*100.0;
                //Ajusta a escala horizontal
                $this->out(sprintf('BT %.2F Tz ET', $horiz_scale));
            } else {
                //Calcula o espaçamento de caracteres em pontos
                $char_space = ($w-$this->cMargin*2-$str_width)/max($this->_MBGetStringLength($txt)-1, 1)*$this->k;
                //Ajusta o espaçamento de caracteres
                $this->out(sprintf('BT %.2F Tc ET', $char_space));
            }
            //Sobrescreve o alinhamento informado (desde que o texto caiba na celula)
            $align = '';
        }
        //Passa para o método cell
        $this->cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
        //Reseta o espaçamento de caracteres e a escala horizontal
        if ($fit) {
            $this->out('BT '.($scale ? '100 Tz' : '0 Tc').' ET');
        }
    }

    /**
     * Celula com escalamento horizontal somente se necessário
     * @param   number  $w
     * @param   number  $h
     * @param   string  $txt
     * @param   number  $border
     * @param   number  $ln
     * @param   string  $align
     * @param   boolean $fill
     * @param   string  $link
     */
    public function cellFitScale($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $this->cellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, true, false);
    }

    /**
     * Celula com escalamento forçado
     * @param   number  $w
     * @param   number  $h
     * @param   string  $txt
     * @param   number  $border
     * @param   number  $ln
     * @param   string  $align
     * @param   boolean $fill
     * @param   string  $link
     */
    public function cellFitScaleForce(
        $w,
        $h = 0,
        $txt = '',
        $border = 0,
        $ln = 0,
        $align = '',
        $fill = false,
        $link = ''
    ) {
        $this->cellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, true, true);
    }

    /**
     * Celula com espaçamento de caracteres somente se necessário
     * @param   number  $w
     * @param   number  $h
     * @param   string  $txt
     * @param   number  $border
     * @param   number  $ln
     * @param   string  $align
     * @param   boolean $fill
     * @param   string  $link
     */
    public function cellFitSpace($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $this->cellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, false, false);
    }
    
    /**
     * Celula com espaçamento de caracteres forçado
     * @param   number  $w
     * @param   number  $h
     * @param   string  $txt
     * @param   number  $border
     * @param   number  $ln
     * @param   string  $align
     * @param   boolean $fill
     * @param   string  $link
     */
    public function cellFitSpaceForce(
        $w,
        $h = 0,
        $txt = '',
        $border = 0,
        $ln = 0,
        $align = '',
        $fill = false,
        $link = ''
    ) {
        $this->cellFit($w, $h, $txt, $border, $ln, $align, $fill, $link, false, true);
    }
    
    /**
     * Patch para trabalhar com textos de duplo byte CJK
     * @param   string $s
     * @return  int
     */
    private function mbGetStringLength($s)
    {
        if ($this->currentFont['type'] == 'Type0') {
            $len = 0;
            $nbbytes = strlen($s);
            for ($i = 0; $i < $nbbytes; $i++) {
                if (ord($s[$i])<128) {
                    $len++;
                } else {
                    $len++;
                    $i++;
                }
            }
            return $len;
        } else {
            return strlen($s);
        }
    }

    /**
     * Desenha uma linha horizontal tracejada com o FPDF
     * @param   number $x Posição horizontal inicial, em mm
     * @param   number $y Posição vertical inicial, em mm
     * @param   number $w Comprimento da linha, em mm
     * @param   number $h Espessura da linha, em mm
     * @param   number $n Numero de traços na seção da linha com o comprimento $w
     * @return  none
     */
    public function dashedHLine($x, $y, $w, $h, $n)
    {
        $this->setDrawColor(110);
        $this->setLineWidth($h);
        $wDash = ($w/$n)/2;
        for ($i=$x; $i<=$x+$w; $i += $wDash+$wDash) {
            for ($j=$i; $j<= ($i+$wDash); $j++) {
                if ($j <= ($x+$w-1)) {
                    $this->line($j, $y, $j+1, $y);
                }
            }
        }
        $this->setDrawColor(0);
    }

   /**
    * Desenha uma linha vertical tracejada com o FPDF
    * @param   number $x      Posição horizontal inicial, em mm
    * @param   number $y      Posição vertical inicial, em mm
    * @param   number $w      Espessura da linha, em mm
    * @param   number $yfinal posição final
    * @param   number $n      Numero de traços na seção da linha com o comprimento $w
    * @return  none
    */
    public function dashedVLine($x, $y, $w, $yfinal, $n)
    {
        $this->setLineWidth($w);
        if ($y > $yfinal) {
            $aux = $yfinal;
            $yfinal = $y;
            $y = $aux;
        }
        while ($y < $yfinal && $n > 0) {
            $this->line($x, $y, $x, $y+1);
            $y += 3;
            $n--;
        }
    }
    
    /**
     * pGetNumLines
     * Obtem o numero de linhas usadas pelo texto usando a fonte especifidada
     * @param  string $text
     * @param  number $width
     * @param  array  $aFont
     * @return number numero de linhas
     */
    public function getNumLines($text, $width, $aFont = ['font' => 'Times', 'size' => 8, 'style' => ''])
    {
        $text = trim($text);
        $this->setFont($aFont['font'], $aFont['style'], $aFont['size']);
        $n = $this->wordWrap($text, $width - 0.2);
        return $n;
    }
    
    /**
     * pTextBox
     * Cria uma caixa de texto com ou sem bordas. Esta função perimite o alinhamento horizontal
     * ou vertical do texto dentro da caixa.
     * Atenção : Esta função é dependente de outras classes de FPDF
     * Ex. $this->pTextBox(2,20,34,8,'Texto',array('fonte'=>$this->fontePadrao,
     * 'size'=>10,'style='B'),'C','L',FALSE,'http://www.nfephp.org')
     *
     * @param  number  $x       Posição horizontal da caixa, canto esquerdo superior
     * @param  number  $y       Posição vertical da caixa, canto esquerdo superior
     * @param  number  $w       Largura da caixa
     * @param  number  $h       Altura da caixa
     * @param  string  $text    Conteúdo da caixa
     * @param  array   $aFont   Matriz com as informações para formatação do texto com fonte, tamanho e estilo
     * @param  string  $vAlign  Alinhamento vertical do texto, T-topo C-centro B-base
     * @param  string  $hAlign  Alinhamento horizontal do texto, L-esquerda, C-centro, R-direita
     * @param  boolean $border  TRUE ou 1 desenha a borda, FALSE ou 0 Sem borda
     * @param  string  $link    Insere um hiperlink
     * @param  boolean $force   Se for true força a caixa com uma unica linha e para isso atera o tamanho do
     * fonte até caber no espaço, se falso mantem o tamanho do fonte e usa quantas linhas forem necessárias
     * e para isso atera o tamanho do fonte até caber no espaço,
     * se falso mantem o tamanho do fonte e usa quantas linhas forem necessárias
     * @param  number  $hmax
     * @param  number  $vOffSet incremento forçado na na posição Y
     * @return number $height Qual a altura necessária para desenhar esta textBox
     */
    public function textBox(
        $x,
        $y,
        $w,
        $h,
        $text = '',
        $aFont = array('font' => 'Times', 'size' => 8, 'style' => ''),
        $vAlign = 'T',
        $hAlign = 'L',
        $border = 1,
        $link = '',
        $force = true,
        $hmax = 0,
        $vOffSet = 0
    ) {
        $oldY = $y;
        $temObs = false;
        $resetou = false;
        if ($w < 0) {
            return $y;
        }
        if (is_object($text)) {
            $text = '';
        }
        if (is_string($text)) {
            //remover espaços desnecessários
            $text = trim($text);
            //converter o charset para o fpdf
            $text = utf8_decode($text);
            //decodifica os caracteres html no xml
            $text = html_entity_decode($text);
        } else {
            $text = (string) $text;
        }
        //desenhar a borda da caixa
        if ($border) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'D');
        }
        //estabelecer o fonte
        $this->setFont($aFont['font'], $aFont['style'], $aFont['size']);
        //calcular o incremento
        $incY = $this->fontSize; //tamanho da fonte na unidade definida
        if (!$force) {
            //verificar se o texto cabe no espaço
            $n = $this->wordWrap($text, $w);
        } else {
            $n = 1;
        }
        //calcular a altura do conjunto de texto
        $altText = $incY * $n;
        //separar o texto em linhas
        $lines = explode("\n", $text);
        //verificar o alinhamento vertical
        if ($vAlign == 'T') {
            //alinhado ao topo
            $y1 = $y + $incY;
        }
        if ($vAlign == 'C') {
            //alinhado ao centro
            $y1 = $y + $incY + (($h - $altText) / 2);
        }
        if ($vAlign == 'B') {
            //alinhado a base
            $y1 = ($y + $h) - 0.5;
        }
        //para cada linha
        foreach ($lines as $line) {
            //verificar o comprimento da frase
            $texto = trim($line);
            $comp = $this->getStringWidth($texto);
            if ($force) {
                $newSize = $aFont['size'];
                while ($comp > $w) {
                    //estabelecer novo fonte
                    $this->setFont($aFont['font'], $aFont['style'], --$newSize);
                    $comp = $this->getStringWidth($texto);
                }
            }
            //ajustar ao alinhamento horizontal
            if ($hAlign == 'L') {
                $x1 = $x + 0.5;
            }
            if ($hAlign == 'C') {
                $x1 = $x + (($w - $comp) / 2);
            }
            if ($hAlign == 'R') {
                $x1 = $x + $w - ($comp + 0.5);
            }
            //escrever o texto
            if ($vOffSet > 0) {
                if ($y1 > ($oldY + $vOffSet)) {
                    if (!$resetou) {
                        $y1 = $oldY;
                        $resetou = true;
                    }
                    $this->text($x1, $y1, $texto);
                }
            } else {
                $this->text($x1, $y1, $texto);
            }
            //incrementar para escrever o proximo
            $y1 += $incY;
            if (($hmax > 0) && ($y1 > ($y + ($hmax - 1)))) {
                $temObs = true;
                break;
            }
        }
        return ($y1 - $y) - $incY;
    }
    
    /**
     * Cria uma caixa de texto com ou sem bordas. Esta função permite o alinhamento horizontal
     * ou vertical do texto dentro da caixa, rotacionando-o em 90 graus, essa função precisa que
     * a classe PDF contenha a função Rotate($angle,$x,$y);
     * Atenção : Esta função é dependente de outras classes de FPDF
     * Ex. $this->__textBox90(2,20,34,8,'Texto',array('fonte'=>$this->fontePadrao,
     * 'size'=>10,'style='B'),'C','L',FALSE,'http://www.nfephp.org')
     * @param  number $x Posição horizontal da caixa, canto esquerdo superior
     * @param  number $y Posição vertical da caixa, canto esquerdo superior
     * @param  number $w Largura da caixa
     * @param  number $h Altura da caixa
     * @param  string $text Conteúdo da caixa
     * @param  array $aFont Matriz com as informações para formatação do texto com fonte, tamanho e estilo
     * @param  string $vAlign Alinhamento vertical do texto, T-topo C-centro B-base
     * @param  string $hAlign Alinhamento horizontal do texto, L-esquerda, C-centro, R-direita
     * @param  boolean $border TRUE ou 1 desenha a borda, FALSE ou 0 Sem borda
     * @param  string $link Insere um hiperlink
     * @param  boolean $force Se for true força a caixa com uma unica linha e para isso atera o tamanho do
     *  fonte até caber no espaço, se falso mantem o tamanho do fonte e usa quantas linhas forem necessárias
     *  linha e para isso atera o tamanho do fonte até caber no espaço,
     *  se falso mantem o tamanho do fonte e usa quantas linhas forem necessárias
     * @param  number  $hmax
     * @param  number  $vOffSet incremento forçado na na posição Y
     * @return number $height Qual a altura necessária para desenhar esta textBox
     */
    public function textBox90(
        $x,
        $y,
        $w,
        $h,
        $text = '',
        $aFont = array('font' => 'Times', 'size' => 8, 'style' => ''),
        $vAlign = 'T',
        $hAlign = 'L',
        $border = 1,
        $link = '',
        $force = true,
        $hmax = 0,
        $vOffSet = 0
    ) {
        $this->rotate(90, $x, $y);
        $oldY = $y;
        $temObs = false;
        $resetou = false;
        if ($w < 0) {
            return $y;
        }
        if (is_object($text)) {
            $text = '';
        }
        if (is_string($text)) {
            //remover espaços desnecessários
            $text = trim($text);
            //converter o charset para o fpdf
            $text = utf8_decode($text);
            //decodifica os caracteres html no xml
            $text = html_entity_decode($text);
        } else {
            $text = (string) $text;
        }
        //desenhar a borda da caixa
        if ($border) {
            $this->roundedRect($x, $y, $w, $h, 0.8, '1234', 'D');
        }
        //estabelecer o fonte
        $this->setFont($aFont['font'], $aFont['style'], $aFont['size']);
        //calcular o incremento
        $incY = $this->fontSize; //tamanho da fonte na unidade definida
        if (!$force) {
            //verificar se o texto cabe no espaço
            $n = $this->wordWrap($text, $w);
        } else {
            $n = 1;
        }
        //calcular a altura do conjunto de texto
        $altText = $incY * $n;
        //separar o texto em linhas
        $lines = explode("\n", $text);
        //verificar o alinhamento vertical
        if ($vAlign == 'T') {
            //alinhado ao topo
            $y1 = $y + $incY;
        }
        if ($vAlign == 'C') {
            //alinhado ao centro
            $y1 = $y + $incY + (($h - $altText) / 2);
        }
        if ($vAlign == 'B') {
            //alinhado a base
            $y1 = ($y + $h) - 0.5;
        }
        //para cada linha
        foreach ($lines as $line) {
            //verificar o comprimento da frase
            $texto = trim($line);
            $comp = $this->getStringWidth($texto);
            if ($force) {
                $newSize = $aFont['size'];
                while ($comp > $w) {
                    //estabelecer novo fonte
                    $this->setFont($aFont['font'], $aFont['style'], --$newSize);
                    $comp = $this->getStringWidth($texto);
                }
            }
            //ajustar ao alinhamento horizontal
            if ($hAlign == 'L') {
                $x1 = $x + 0.5;
            }
            if ($hAlign == 'C') {
                $x1 = $x + (($w - $comp) / 2);
            }
            if ($hAlign == 'R') {
                $x1 = $x + $w - ($comp + 0.5);
            }
            //escrever o texto
            if ($vOffSet > 0) {
                if ($y1 > ($oldY + $vOffSet)) {
                    if (!$resetou) {
                        $y1 = $oldY;
                        $resetou = true;
                    }
                    $this->text($x1, $y1, $texto);
                }
            } else {
                $this->text($x1, $y1, $texto);
            }
            //incrementar para escrever o proximo
            $y1 += $incY;
            if (($hmax > 0) && ($y1 > ($y + ($hmax - 1)))) {
                $temObs = true;
                break;
            }
        }
        //Zerando rotação
        $this->rotate(0, $x, $y);
        return ($y1 - $y) - $incY;
    }
}
