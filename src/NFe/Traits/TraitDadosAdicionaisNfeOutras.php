<?php

namespace NFePHP\DA\NFe\Traits;

trait TraitDadosAdicionaisNfeOutras
{
    public function dadosAdicionaisOutras(string $infCpl = '', string $infAdFisco = ''): float
    {
        if (empty($infCpl) && empty($infAdFisco)) {
            return 0;
        }
        $dados = "dadosAdicionaisOutras{$this->orientacao}";
        return $this->$dados($infCpl, $infAdFisco);
    }

    public function dadosAdicionaisOutrasP(string $infCpl = '', string $infAdFisco = ''): float
    {
        return 0;
    }

    public function dadosAdicionaisOutrasL(string $infCpl = '', string $infAdFisco = ''): float
    {
        return 0;
    }
}
