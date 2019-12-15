<?php

namespace NFePHP\DA\Legacy;

class Common
{

    /**
     * Extrai o valor do node DOM
     * @param  object $theObj Instancia de DOMDocument ou DOMElement
     * @param  string $keyName identificador da TAG do xml
     * @param  string $extraTextBefore prefixo do retorno
     * @param  string extraTextAfter sufixo do retorno
     * @param  number itemNum numero do item a ser retornado
     * @return string
     */
    protected function getTagValue($theObj, $keyName, $extraTextBefore = '', $extraTextAfter = '', $itemNum = 0)
    {
        if (empty($theObj)) {
            return '';
        }
        $vct = $theObj->getElementsByTagName($keyName)->item($itemNum);
        if (isset($vct)) {
            $value = trim($vct->nodeValue);
            if (strpos($value, '&') !== false) {
                //existe um & na string, então deve ser uma entidade
                $value = html_entity_decode($value);
            }
            return $extraTextBefore . $value . $extraTextAfter;
        }
        return '';
    }

    /**
     * Recupera e reformata a data do padrão da NFe para dd/mm/aaaa
     * @author Marcos Diez
     * @param  DOM    $theObj
     * @param  string $keyName   identificador da TAG do xml
     * @param  string $extraText prefixo do retorno
     * @return string
     */
    protected function getTagDate($theObj, $keyName, $extraText = '')
    {
        if (!isset($theObj) || !is_object($theObj)) {
            return '';
        }
        $vct = $theObj->getElementsByTagName($keyName)->item(0);
        if (isset($vct)) {
            $theDate = explode("-", $vct->nodeValue);
            return $extraText . $theDate[2] . "/" . $theDate[1] . "/" . $theDate[0];
        }
        return '';
    }

    /**
     * camcula digito de controle modulo 11
     * @param  string $numero
     * @return integer modulo11 do numero passado
     */
    protected function modulo11($numero = '')
    {
        if ($numero == '') {
            return '';
        }
        $numero = (string) $numero;
        $tamanho = strlen($numero);
        $soma = 0;
        $mult = 2;
        for ($i = $tamanho - 1; $i >= 0; $i--) {
            $digito = (int) $numero[$i];
            $r = $digito * $mult;
            $soma += $r;
            $mult++;
            if ($mult == 10) {
                $mult = 2;
            }
        }
        $resto = ($soma * 10) % 11;
        return ($resto == 10 || $resto == 0) ? 1 : $resto;
    }

    /**
     * Converte datas no formato YMD (ex. 2009-11-02) para o formato brasileiro 02/11/2009)
     * @param  string $data Parâmetro extraido da NFe
     * @return string Formatada para apresentação da data no padrão brasileiro
     */
    protected function ymdTodmy($data = '')
    {
        if ($data == '') {
            return '';
        }
        $needle = "/";
        if (strstr($data, "-")) {
            $needle = "-";
        }
        $dt = explode($needle, $data);
        return "$dt[2]/$dt[1]/$dt[0]";
    }

    /**
     * Converte data da NFe YYYY-mm-ddThh:mm:ss-03:00 para timestamp unix
     *
     * @param string $input
     *
     * @return integer
     */
    public function toTimestamp($input)
    {
        $regex = '^(2[0-9][0-9][0-9])[-](0?[1-9]'
            . '|1[0-2])[-](0?[1-9]'
            . '|[12][0-9]'
            . '|3[01])T([0-9]|0[0-9]'
            . '|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]-(00|01|02|03|04):00$';
        
        if (!preg_match("/$regex/", $input)) {
            return '';
        }
        return \DateTime::createFromFormat("Y-m-d\TH:i:sP", $input)->getTimestamp();
    }

    /**
     * Função de formatação de strings onde o cerquilha # é um coringa
     * que será substituido por digitos contidos em campo.
     * @param  string $campo   String a ser formatada
     * @param  string $mascara Regra de formatção da string (ex. ##.###.###/####-##)
     * @return string Retorna o campo formatado
     */
    protected function formatField($campo = '', $mascara = '')
    {
        if ($campo == '' || $mascara == '') {
            return $campo;
        }
        //remove qualquer formatação que ainda exista
        $sLimpo = preg_replace("(/[' '-./ t]/)", '', $campo);
        // pega o tamanho da string e da mascara
        $tCampo = strlen($sLimpo);
        $tMask = strlen($mascara);
        if ($tCampo > $tMask) {
            $tMaior = $tCampo;
        } else {
            $tMaior = $tMask;
        }
        //contar o numero de cerquilhas da mascara
        $aMask = str_split($mascara);
        $z = 0;
        $flag = false;
        foreach ($aMask as $letra) {
            if ($letra == '#') {
                $z++;
            }
        }
        if ($z > $tCampo) {
            //o campo é menor que esperado
            $flag = true;
        }
        //cria uma variável grande o suficiente para conter os dados
        $sRetorno = '';
        $sRetorno = str_pad($sRetorno, $tCampo + $tMask, " ", STR_PAD_LEFT);
        //pega o tamanho da string de retorno
        $tRetorno = strlen($sRetorno);
        //se houve entrada de dados
        if ($sLimpo != '' && $mascara != '') {
            //inicia com a posição do ultimo digito da mascara
            $x = $tMask;
            $y = $tCampo;
            $cI = 0;
            for ($i = $tMaior - 1; $i >= 0; $i--) {
                if ($cI < $z) {
                    // e o digito da mascara é # trocar pelo digito do campo
                    // se o inicio da string da mascara for atingido antes de terminar
                    // o campo considerar #
                    if ($x > 0) {
                        $digMask = $mascara[--$x];
                    } else {
                        $digMask = '#';
                    }
                    //se o fim do campo for atingido antes do fim da mascara
                    //verificar se é ( se não for não use
                    if ($digMask == '#') {
                        $cI++;
                        if ($y > 0) {
                            $sRetorno[--$tRetorno] = $sLimpo[--$y];
                        } else {
                            //$sRetorno[--$tRetorno] = '';
                        }
                    } else {
                        if ($y > 0) {
                            $sRetorno[--$tRetorno] = $mascara[$x];
                        } else {
                            if ($mascara[$x] == '(') {
                                $sRetorno[--$tRetorno] = $mascara[$x];
                            }
                        }
                        $i++;
                    }
                }
            }
            if (!$flag) {
                if ($mascara[0] != '#') {
                    $sRetorno = '(' . trim($sRetorno);
                }
            }
            return trim($sRetorno);
        } else {
            return '';
        }
    }

    protected function tipoPag($tPag)
    {
        switch ($tPag) {
            case '01':
                $tPagNome = 'Dinheiro';
                break;
            case '02':
                $tPagNome = 'Cheque';
                break;
            case '03':
                $tPagNome = 'Cartão de Crédito';
                break;
            case '04':
                $tPagNome = 'Cartão de Débito';
                break;
            case '05':
                $tPagNome = 'Crédito Loja';
                break;
            case '10':
                $tPagNome = 'Vale Alimentação';
                break;
            case '11':
                $tPagNome = 'Vale Refeição';
                break;
            case '12':
                $tPagNome = 'Vale Presente';
                break;
            case '13':
                $tPagNome = 'Vale Combustível';
                break;
            case '14':
                $tPagNome = 'Duplicata Mercantil';
                break;
            case '15':
                $tPagNome = 'Boleto Bancário';
                break;
            case '90':
                $tPagNome = 'Sem Pagamento';
                break;
            case '99':
                $tPagNome = 'Outros';
                break;
            default:
                $tPagNome = '';
                // Adicionado default para impressão de notas da 3.10
        }
        return $tPagNome;
    }
}
