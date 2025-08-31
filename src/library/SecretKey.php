<?php

namespace StripePayment\library;

class SecretKey
{
    private $company;
    private $skey;

    public function __construct($company)
    {
        $this->company = $company;
    }

    public function getKey($type): string
    {
        $search = sql([
            "statement" => "SELECT * FROM pagamentos.empresas WHERE id = ?",
            "types" => "i",
            "parameters" => [
                $this->company
            ],
            "only_first_row" => "1"
        ]);

        $this->skey = ($type == "sandbox") ? $search['sandbox_secret'] : $search['producao_secret'];
        return $this->skey;
    }
}